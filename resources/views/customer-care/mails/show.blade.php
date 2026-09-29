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
                                $mime = strtolower((string) $file->mime_type);
                                $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'], true)
                                    || (in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/bmp'], true));
                                $isVideo = in_array($ext, ['mp4', 'webm', 'ogv', 'mov'], true)
                                    || in_array($mime, ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'], true);
                                $isPdf = $ext === 'pdf' || $mime === 'application/pdf';
                                $url = route('customer-care.mails.attachment', [$mail->id, $file->id]);
                                $bytes = (int) $file->size_bytes;
                                $size = $bytes === 0
                                    ? null
                                    : ($bytes >= 1048576
                                        ? number_format($bytes / 1048576, 1) . ' MB'
                                        : number_format($bytes / 1024, 0) . ' KB');
                                $icon = $isImage ? 'fa-file-image' : ($isVideo ? 'fa-file-video' : ($isPdf ? 'fa-file-pdf' : 'fa-file'));
                                $iconColor = $isImage ? 'text-green-600' : ($isVideo ? 'text-purple-600' : ($isPdf ? 'text-red-600' : 'text-blue-600'));
                            @endphp
                            <li class="flex items-center gap-3 border border-gray-200 rounded-lg p-3">
                                <i class="fas {{ $icon }} {{ $iconColor }} text-xl w-6 text-center"></i>
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                       class="text-sm font-medium text-gray-800 hover:text-blue-700 truncate block">
                                        {{ $file->file_name }}{{ $ext === '' && $size !== null ? ' (' . ($size) . ')' : '' }}
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

                        {{-- Pictures play and show here, so a screenshot of a
                             question or a short recording can be read without
                             a download. Everything else is opened from its
                             row above. --}}
                        @php
                            $previewable = $attachments->filter(function ($f) {
                                $extension = strtolower(pathinfo($f->file_name, PATHINFO_EXTENSION));
                                $type = strtolower((string) $f->mime_type);

                                return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)
                                    || in_array($type, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)
                                    || in_array($extension, ['mp4', 'webm', 'ogv'], true)
                                    || in_array($type, ['video/mp4', 'video/webm', 'video/ogg'], true);
                            });
                        @endphp

                        @if($previewable->isNotEmpty())
                            <div class="mt-3 space-y-3">
                                @foreach($previewable as $file)
                                    @php
                                        $previewUrl = route('customer-care.mails.attachment', [$mail->id, $file->id]);
                                        $extension = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                        $type = strtolower((string) $file->mime_type);
                                        $isVideo = in_array($extension, ['mp4', 'webm', 'ogv'], true)
                                            || in_array($type, ['video/mp4', 'video/webm', 'video/ogg'], true);
                                    @endphp

                                    @if($isVideo)
                                        {{-- controls, and no autoplay: these files can be
                                             large and nobody wants sound from a mailbox. --}}
                                        <video controls preload="none" class="w-full rounded-lg border border-gray-200 bg-black"
                                               style="max-height: 420px;">
                                            <source src="{{ $previewUrl }}">
                                            Your browser cannot play this file. Use Download.
                                        </video>
                                    @else
                                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                                           class="block border border-gray-200 rounded-lg overflow-hidden hover:border-blue-300">
                                            <img src="{{ $previewUrl }}" alt="{{ $file->file_name }}"
                                                 class="w-full object-contain bg-white" style="max-height: 420px;">
                                        </a>
                                    @endif
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

            {{-- Body. Only the plain text is shown. The sender controls the HTML,
                 and a formatted rendering gets in the way of reading a message
                 rather than helping it. An HTML-only mail is converted to text
                 when it arrives, so nothing is lost by dropping the rich view. --}}
            <div class="px-5 py-4">
                <div class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">{{ $mail->body_text ?: 'This mail has no readable body.' }}</div>
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
                            @if(! empty($reply->attachment_names))
                                <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-gray-500">
                                    <i class="fas fa-paperclip text-gray-400"></i>
                                    @foreach(array_filter(array_map('trim', explode(',', (string) $reply->attachment_names))) as $name)
                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-600">{{ $name }}</span>
                                    @endforeach
                                </p>
                            @endif
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
            <form method="POST" action="{{ route('customer-care.mails.reply', $mail->id) }}" enctype="multipart/form-data">
                @csrf
                <textarea name="body" rows="6" required
                          placeholder="Type your answer as {{ $signature }}…"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mb-3">{{ old('body') }}</textarea>

                {{-- Attachments travel as multipart, so the form cannot be a
                     plain POST any more. The names are listed on the client
                     because the picked files never reach the page again if the
                     send is refused. --}}
                <div class="mb-3">
                    <label for="reply-attachments" class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                        <i class="fas fa-paperclip text-gray-400"></i>
                        Attach files, up to {{ $replyLimits['files'] }} and {{ $replyLimits['totalMb'] }} MB in total
                        ({{ $replyLimits['fileMb'] }} MB each)
                    </label>
                    <input id="reply-attachments" name="attachments[]" type="file" multiple
                           class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                  file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200
                                  focus:ring-2 focus:ring-blue-500"
                           data-max-files="{{ $replyLimits['files'] }}"
                           data-max-total="{{ $replyLimits['totalMb'] * 1048576 }}">
                    <ul id="reply-attachment-list" class="mt-1 hidden space-y-1"></ul>
                    <p class="mt-1 text-xs text-red-600 hidden" id="reply-attachment-error"></p>
                    <p class="mt-1 text-xs text-gray-400">
                        Photos, documents, archives and video. A single file over {{ $replyLimits['fileMb'] }} MB is refused.
                    </p>
                </div>

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
    // Picked attachments. The list is a convenience, not a gate: the server
    // applies the same limits and is the one that actually decides. The total
    // is checked here too because exceeding post_max_size does not produce a
    // validation error, it produces a bare 419 with no explanation.
    (function () {
        var input = document.getElementById('reply-attachments');
        var list = document.getElementById('reply-attachment-list');
        var error = document.getElementById('reply-attachment-error');
        if (!input || !list || !error) {
            return;
        }

        var maxFiles = parseInt(input.getAttribute('data-max-files'), 10) || 5;
        var maxTotal = parseInt(input.getAttribute('data-max-total'), 10) || 0;

        function megabytes(bytes) {
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files || []);
            var total = files.reduce(function (sum, file) { return sum + file.size; }, 0);
            var problem = '';

            if (files.length > maxFiles) {
                problem = 'Only ' + maxFiles + ' files can go on one answer.';
            } else if (maxTotal && total > maxTotal) {
                problem = 'Those files add up to ' + megabytes(total)
                    + ', which is over the ' + megabytes(maxTotal) + ' limit.';
            }

            error.textContent = problem;
            error.classList.toggle('hidden', !problem);

            list.innerHTML = '';
            list.classList.toggle('hidden', files.length === 0);
            files.forEach(function (file) {
                var item = document.createElement('li');
                item.className = 'flex items-center justify-between gap-2 rounded border border-gray-200 px-2 py-1 text-xs text-gray-700';
                var name = document.createElement('span');
                name.className = 'truncate';
                name.textContent = file.name;
                var size = document.createElement('span');
                size.className = 'text-gray-400 shrink-0';
                size.textContent = megabytes(file.size);
                item.appendChild(name);
                item.appendChild(size);
                list.appendChild(item);
            });
        });
    })();
</script>
@endpush
@endsection
