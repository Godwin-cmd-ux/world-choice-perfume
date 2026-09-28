@extends('layouts.app')
@section('title', 'Mail')
@section('header', 'Mail — ' . ($mail->subject ?: '(no subject)'))
@section('content')

@php
    $repliesForThis = collect($replies)->filter(
        fn ($r) => (int) ($r->info_email_id ?? 0) === (int) $mail->id
    )->values();
    $when = $mail->received_at ?: ($mail->created_at ?? null);
    $stamp = fn ($value) => $value
        ? \Carbon\Carbon::parse($value)->setTimezone('Africa/Dar_es_Salaam')->format('M d, Y H:i')
        : '—';
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- The conversation --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-xl shadow">
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-semibold text-lg leading-snug">{{ $mail->subject ?: '(no subject)' }}</h3>
                    @if($mail->is_starred ?? false)
                        <i class="fas fa-star text-amber-500 mt-1"></i>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mt-1">
                    <span class="font-medium text-gray-800">{{ $mail->from_name ?: 'Unknown sender' }}</span>
                    &lt;<a href="mailto:{{ $mail->from_email }}" class="text-blue-600 hover:underline">{{ $mail->from_email }}</a>&gt;
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    to {{ $mail->to_email ?: config('info_mail.address') }}
                    @if(! empty($mail->cc)) · cc {{ $mail->cc }} @endif
                    · {{ $stamp($when) }}
                </p>
            </div>

            @if($mail->has_attachments ?? false)
                <div class="px-5 py-4 border-b border-gray-100">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">
                        <i class="fas fa-paperclip mr-1 text-gray-400"></i>
                        {{ count($attachments) ?: count(array_filter(explode(', ', (string) $mail->attachment_names))) }}
                        attachment(s)
                    </p>

                    @if($attachments)
                        <ul class="space-y-2">
                            @foreach($attachments as $file)
                                @php
                                    $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'], true);
                                    $isPdf = $ext === 'pdf';
                                    $url = route('customer-care.mails.attachment', [$mail->id, $file->id]);
                                    $bytes = (int) $file->size_bytes;
                                    $size = $bytes === 0
                                        ? null
                                        : ($bytes >= 1048576
                                            ? number_format($bytes / 1048576, 1) . ' MB'
                                            : number_format($bytes / 1024, 0) . ' KB');
                                    $icon = $isImage ? 'fa-file-image' : ($isPdf ? 'fa-file-pdf' : 'fa-file');
                                    $iconColor = $isImage ? 'text-green-600' : ($isPdf ? 'text-red-600' : 'text-blue-600');
                                @endphp
                                <li class="flex items-center gap-3 border border-gray-200 rounded-lg p-3">
                                    <i class="fas {{ $icon }} {{ $iconColor }} text-xl w-6 text-center"></i>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ $url }}" target="_blank" rel="noopener"
                                           class="text-sm font-medium text-gray-800 hover:text-blue-700 truncate block">
                                            {{ $file->file_name }}
                                        </a>
                                        <p class="text-[11px] text-gray-400">{{ $file->mime_type }}{{ $size ? ' · ' . $size : '' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <a href="{{ $url }}" target="_blank" rel="noopener"
                                           class="px-2.5 py-1 rounded-lg border border-gray-300 text-xs text-gray-600 hover:bg-gray-50">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <a href="{{ $url }}?download=1"
                                           class="px-2.5 py-1 rounded-lg text-xs text-blue-700 border border-blue-200 hover:bg-blue-50">
                                            <i class="fas fa-download mr-1"></i> Download
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        {{-- Pictures get an inline preview so a screenshot of a
                             question can be read without a download. --}}
                        @if(collect($attachments)->contains(fn ($f) => in_array(strtolower(pathinfo($f->file_name, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)))
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($attachments as $file)
                                    @continue(! in_array(strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'webp'], true))
                                    <a href="{{ route('customer-care.mails.attachment', [$mail->id, $file->id]) }}"
                                       target="_blank" rel="noopener" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-blue-300">
                                        <img src="{{ route('customer-care.mails.attachment', [$mail->id, $file->id]) }}"
                                             alt="{{ $file->file_name }}"
                                             class="w-full object-cover" style="max-height: 320px;">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        @php $names = array_filter(explode(', ', (string) $mail->attachment_names)); @endphp
                        <p class="text-xs text-gray-500">
                            <span class="font-medium">{{ $mail->attachment_names }}</span>
                            <span class="text-gray-400">
                                — {{ count($names) === 1 ? 'this file was' : 'these files were' }} received before
                                attachments were being kept, so only {{ count($names) === 1 ? 'its name is' : 'their names are' }}
                                on record. Ask the customer to resend.
                            </span>
                        </p>
                    @endif
                </div>
            @endif

            {{-- Body. HTML mails are shown in a sandboxed frame with scripts
                 and same-origin access switched off, because the sender
                 controls that markup. --}}
            <div class="px-5 py-4">
                @if($mail->body_html)
                    <div class="flex items-center gap-2 mb-3">
                        <button type="button" data-mail-tab="html"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white">Rich view</button>
                        <button type="button" data-mail-tab="text"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 text-gray-600">Plain text</button>
                    </div>

                    <div data-mail-panel="html">
                        <iframe id="mailFrame" title="Message content" sandbox
                                class="w-full border border-gray-200 rounded-lg bg-white"
                                style="height: 520px;"></iframe>
                        <script>
                            (function () {
                                var frame = document.getElementById('mailFrame');
                                if (!frame) return;
                                // base64 so the sender's markup can never break
                                // out of the attribute it is written into.
                                var html = atob(@json(base64_encode((string) $mail->body_html)));
                                frame.srcdoc = html;
                            })();
                        </script>
                    </div>

                    <div data-mail-panel="text" class="hidden">
                        <pre class="whitespace-pre-wrap font-sans text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-4">{{ $mail->body_text }}</pre>
                    </div>
                @else
                    <div class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">{{ $mail->body_text ?: 'This mail has no readable body.' }}</div>
                @endif
            </div>
        </div>

        {{-- Answers already sent inside this thread --}}
        @if($repliesForThis->isNotEmpty())
            <div class="bg-white rounded-xl shadow p-5">
                <h4 class="font-semibold mb-3"><i class="fas fa-reply text-green-600 mr-1"></i> Answers sent</h4>
                <div class="space-y-3">
                    @foreach($repliesForThis as $reply)
                        <div class="border-l-4 pl-4 py-1 {{ ($reply->status ?? '') === 'failed' ? 'border-red-400' : 'border-green-500' }}">
                            <p class="text-xs text-gray-500">
                                To <span class="font-medium text-gray-700">{{ $reply->to_email }}</span>
                                · {{ $reply->sent_by_name ?: 'Customer Care' }}
                                · {{ $stamp($reply->sent_at ?: ($reply->created_at ?? null)) }}
                                @if(($reply->status ?? '') === 'failed')
                                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] bg-red-100 text-red-700">not delivered</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $reply->body }}</p>
                            @if(! empty($reply->error))
                                <p class="text-xs text-red-600 mt-1">{{ $reply->error }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Other messages in the same conversation --}}
        @if($thread->count() > 1)
            <div class="bg-white rounded-xl shadow p-5">
                <h4 class="font-semibold mb-3"><i class="fas fa-comments text-blue-600 mr-1"></i> Conversation ({{ $thread->count() }} messages)</h4>
                <div class="space-y-3">
                    @foreach($thread as $item)
                        @continue((int) $item->id === (int) $mail->id)
                        <div class="border-l-4 border-gray-300 pl-4 py-1">
                            <p class="text-xs text-gray-500">
                                From <span class="font-medium text-gray-700">{{ $item->from_name ?: $item->from_email }}</span>
                                · {{ $stamp($item->received_at ?: ($item->created_at ?? null)) }}
                            </p>
                            <a href="{{ route('customer-care.mails.show', $item->id) }}" class="text-sm text-blue-600 hover:underline">
                                {{ $item->subject ?: '(no subject)' }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit((string) ($item->body_text ?? ''), 160) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Reply --}}
        <div class="bg-white rounded-xl shadow p-5">
            <h4 class="font-semibold mb-1">Reply</h4>
            <p class="text-xs text-gray-500 mb-3">
                Sends from <span class="font-medium">{{ config('info_mail.address') }}</span> to
                <span class="font-medium">{{ $mail->from_email }}</span>, subject
                <span class="font-medium">Re: {{ preg_replace('/^(re:\s*)+/i', '', (string) ($mail->subject ?? '')) ?: 'Your message' }}</span>.
            </p>
            <form method="POST" action="{{ route('customer-care.mails.reply', $mail->id) }}">
                @csrf
                <textarea name="body" rows="6" required
                          placeholder="Type your answer as {{ $signature }}…"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mb-3">{{ old('body') }}</textarea>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" style="background-color: #F89A1E;" class="hover:opacity-90 text-white px-6 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-paper-plane mr-1"></i> Send Reply
                    </button>
                    <span class="text-xs text-gray-400">The customer's own message is quoted underneath your answer.</span>
                </div>
            </form>
        </div>
    </div>

    {{-- Sidebar: the facts about this mail --}}
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3">Actions</h3>
            <div class="flex flex-col gap-2">
                <form action="{{ route('customer-care.mails.read', $mail->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas {{ ($mail->is_read ?? false) ? 'fa-envelope' : 'fa-envelope-open' }} mr-1"></i>
                        Mark as {{ ($mail->is_read ?? false) ? 'unread' : 'read' }}
                    </button>
                </form>
                <form action="{{ route('customer-care.mails.star', $mail->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-star mr-1 text-amber-500"></i>
                        {{ ($mail->is_starred ?? false) ? 'Remove star' : 'Star this mail' }}
                    </button>
                </form>

                @if(($mail->status ?? 'new') !== 'closed')
                    <form action="{{ route('customer-care.mails.status', $mail->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="closed">
                        <button type="submit" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-check-circle mr-1 text-green-600"></i> Close (no answer needed)
                        </button>
                    </form>
                @else
                    <form action="{{ route('customer-care.mails.status', $mail->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="new">
                        <button type="submit" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-folder-open mr-1"></i> Reopen
                        </button>
                    </form>
                @endif

                <form action="{{ route('customer-care.mails.destroy', $mail->id) }}" method="POST"
                      onsubmit="return confirm('Delete this mail from the mailbox? This cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full border border-red-200 rounded-lg px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="font-semibold mb-3">Details</h3>
            <div class="space-y-2 text-sm break-all">
                <div><span class="text-gray-500">From:</span> {{ $mail->from_email }}</div>
                <div><span class="text-gray-500">To:</span> {{ $mail->to_email ?: config('info_mail.address') }}</div>
                @if(! empty($mail->cc))<div><span class="text-gray-500">Cc:</span> {{ $mail->cc }}</div>@endif
                <div><span class="text-gray-500">Received:</span> {{ $stamp($when) }}</div>
                <div><span class="text-gray-500">Status:</span> {{ ucfirst((string) ($mail->status ?? 'new')) }}</div>
                @if(! empty($mail->spf_result))
                    <div>
                        <span class="text-gray-500">SPF:</span>
                        <span class="{{ $mail->spf_result === 'pass' ? 'text-green-600' : 'text-amber-600' }}">{{ $mail->spf_result }}</span>
                    </div>
                @endif
                @if(! empty($mail->dkim_result))
                    <div>
                        <span class="text-gray-500">DKIM:</span>
                        <span class="{{ $mail->dkim_result === 'pass' ? 'text-green-600' : 'text-amber-600' }}">{{ $mail->dkim_result }}</span>
                    </div>
                @endif
                @if(! empty($mail->message_id))
                    <div><span class="text-gray-500">Message-ID:</span> <span class="text-xs text-gray-400">{{ $mail->message_id }}</span></div>
                @endif
            </div>
            @if(($mail->spf_result ?? '') !== 'pass' && ! empty($mail->spf_result))
                <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2">
                    SPF is <strong>{{ $mail->spf_result }}</strong>, so the sending server is not listed for
                    {{ $mail->from_email }}. Treat anything asking for money or passwords with care.
                </p>
            @endif
        </div>
    </div>
</div>

<div class="mt-6">
    <a href="{{ route('customer-care.mails.index') }}" class="text-blue-600 hover:underline text-sm">
        <i class="fas fa-arrow-left mr-1"></i> Back to Mails
    </a>
</div>

@push('scripts')
<script>
    // Rich / plain toggle for the message body.
    document.querySelectorAll('[data-mail-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            var wanted = button.getAttribute('data-mail-tab');
            document.querySelectorAll('[data-mail-tab]').forEach(function (other) {
                other.className = other.getAttribute('data-mail-tab') === wanted
                    ? 'px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white'
                    : 'px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 text-gray-600';
            });
            document.querySelectorAll('[data-mail-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-mail-panel') !== wanted);
            });
        });
    });
</script>
@endpush
@endsection
