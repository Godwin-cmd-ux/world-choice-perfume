@extends('layouts.app')
@section('title', 'Mails')
@section('header', 'Mails — info@worldchoiceperfume.com')
@section('content')

{{-- Left rail of boxes, then the list. Same light admin styling as Inquiries. --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow p-3 sticky top-6">
            @php
                $boxes = [
                    'all' => ['All mail', 'fa-inbox', null],
                    'unread' => ['Unread', 'fa-envelope', $unreadCount ?: null],
                    'starred' => ['Starred', 'fa-star', null],
                    'replied' => ['Replied', 'fa-reply', null],
                    'closed' => ['Closed', 'fa-check-circle', null],
                ];
            @endphp
            <nav class="space-y-1">
                @foreach($boxes as $key => [$label, $icon, $count])
                    <a href="{{ route($mailRoute.'.index', array_filter(['box' => $key !== 'all' ? $key : null, 'search' => $search])) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $box === $key ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                        <i class="fas {{ $icon }} w-4 text-center"></i>
                        <span class="flex-1">{{ $label }}</span>
                        @if($count)
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $box === $key ? 'bg-white/25' : 'bg-blue-100 text-blue-700' }}">{{ $count }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-[10px] uppercase tracking-wider text-gray-400 px-3 mb-2">Mailbox</p>
                <p class="px-3 text-sm text-gray-700 break-all">{{ config('info_mail.address') }}</p>
                <p class="px-3 mt-1 text-[11px] text-gray-400 leading-relaxed">
                    Replies leave from this address. Anything sent here is filed automatically.
                </p>
            </div>
        </div>
    </div>

    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl shadow p-4 mb-4">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                @if($box !== 'all')
                    <input type="hidden" name="box" value="{{ $box }}">
                @endif
                <div class="flex-1 min-w-[220px]">
                    <label class="block text-[10px] uppercase tracking-wider text-gray-500 mb-1" for="mail-search">Search</label>
                    <input id="mail-search" type="search" name="search" value="{{ $search }}"
                           placeholder="Sender, subject or a word in the message"
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" style="background-color: #F89A1E;" class="text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                @if($search !== '')
                    <a href="{{ route($mailRoute.'.index', array_filter(['box' => $box !== 'all' ? $box : null])) }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50">Clear</a>
                @endif
            </form>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-8 px-4"></th>
                            <th class="text-left py-3 px-4">Subject</th>
                            <th class="text-left px-4">From</th>
                            <th class="text-left px-4">Status</th>
                            <th class="text-left px-4">Received</th>
                            <th class="text-left px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mails as $mail)
                            @php
                                $when = $mail->received_at ?: ($mail->created_at ?? null);
                                $preview = trim(preg_replace('/\s+/', ' ', (string) ($mail->body_text ?? '')) ?? '');
                                $unread = ! ($mail->is_read ?? false);
                            @endphp
                            {{-- The whole row opens the mail. The link on the subject
                                 carries an overlay that covers the row, so clicking
                                 anywhere lands on it; the action buttons sit above
                                 that overlay and keep their own clicks. --}}
                            <tr class="relative border-t hover:bg-gray-50 {{ $unread ? 'bg-blue-50' : '' }}">
                                <td class="px-4">
                                    @if($mail->is_starred ?? false)
                                        <i class="fas fa-star text-amber-500 text-xs"></i>
                                    @elseif($unread)
                                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <a href="{{ route($mailRoute.'.show', $mail->id) }}"
                                       class="font-medium hover:text-blue-700 after:content-[''] after:absolute after:inset-0 {{ $unread ? 'text-gray-900' : 'text-gray-700' }}">
                                        {{ $mail->subject ?: '(no subject)' }}
                                    </a>
                                    <p class="text-xs text-gray-400 truncate">{{ \Illuminate\Support\Str::limit($preview, 90) }}</p>
                                </td>
                                <td class="px-4 text-gray-500">
                                    <div class="truncate max-w-[16rem]">{{ $mail->from_name ?: $mail->from_email }}</div>
                                    <div class="text-[11px] text-gray-400 truncate max-w-[16rem]">{{ $mail->from_email }}</div>
                                </td>
                                <td class="px-4">
                                    @if(($mail->status ?? 'new') === 'replied')
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Replied</span>
                                    @elseif(($mail->status ?? '') === 'closed')
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Closed</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-600">New</span>
                                    @endif
                                    @if($mail->has_attachments ?? false)
                                        <i class="fas fa-paperclip text-gray-400 text-xs ml-1" title="{{ $mail->attachment_names }}"></i>
                                    @endif
                                </td>
                                <td class="px-4 text-xs text-gray-500 whitespace-nowrap">
                                    {{ $when ? \Carbon\Carbon::parse($when)->setTimezone('Africa/Dar_es_Salaam')->format('M d, H:i') : '—' }}
                                </td>
                                <td class="px-4">
                                    <div class="flex items-center gap-2 whitespace-nowrap relative z-10">
                                        <form action="{{ route($mailRoute.'.read', $mail->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:underline text-xs font-medium">
                                                <i class="fas {{ $unread ? 'fa-envelope' : 'fa-envelope-open' }} mr-1"></i>
                                                {{ $unread ? 'Unread' : 'Read' }}
                                            </button>
                                        </form>
                                        <form action="{{ route($mailRoute.'.star', $mail->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-amber-600 hover:underline text-xs font-medium">
                                                <i class="fas fa-star mr-1"></i>{{ ($mail->is_starred ?? false) ? 'Unstar' : 'Star' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-14 text-center text-gray-400">
                                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                    @if($search !== '')
                                        No mail matches “{{ $search }}”.
                                    @else
                                        Nothing in this box yet. Mail sent to {{ config('info_mail.address') }} will land here.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($shown >= (int) config('info_mail.per_page', 30))
            <div class="flex items-center justify-between mt-4 text-sm">
                <a href="{{ route($mailRoute.'.index', array_filter(['box' => $box !== 'all' ? $box : null, 'search' => $search, 'page' => $page > 1 ? $page - 1 : null])) }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 {{ $page <= 1 ? 'pointer-events-none opacity-40' : 'hover:bg-white' }}">
                    <i class="fas fa-chevron-left mr-1"></i> Newer
                </a>
                <span class="text-gray-500">Page {{ $page }}</span>
                <a href="{{ route($mailRoute.'.index', array_filter(['box' => $box !== 'all' ? $box : null, 'search' => $search, 'page' => $page + 1])) }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-600 hover:bg-white">
                    Older <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
