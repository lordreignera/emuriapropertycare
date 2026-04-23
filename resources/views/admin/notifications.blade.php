@php
    $isClientUser = auth()->user()?->hasRole('Client');
    $layout = $isClientUser ? 'client.layout' : 'admin.layout';
    $notificationsOpenRouteName = $isClientUser ? 'client.notifications.open' : 'notifications.open';
    $notificationsReadAllRoute = $isClientUser ? route('client.notifications.read-all') : route('notifications.read-all');
@endphp

@extends($layout)

@section('title', 'Notifications')
@section('header', 'Notifications')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Notifications</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card notification-page-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h4 class="card-title mb-1">All Notifications</h4>
                        <p class="text-muted mb-0">Track report updates, quotations, schedules, and workflow alerts in one place.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-danger fs-6">Unread: {{ $unreadNotificationsCount }}</span>
                        @if($unreadNotificationsCount > 0)
                            <form method="POST" action="{{ $notificationsReadAllRoute }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-check-all me-1"></i>Mark all as read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($notifications->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="mdi mdi-bell-off-outline text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mb-2">No notifications yet</h5>
                        <p class="text-muted mb-0">New updates will appear here as your workflow progresses.</p>
                    </div>
                @else
                    <div class="notification-list d-grid gap-3">
                        @foreach($notifications as $notification)
                            @php
                                $isUnread = $notification->read_at === null;
                                $notificationTitle = $notification->data['title'] ?? 'Notification';
                                $notificationMessage = $notification->data['message'] ?? 'Open to view more details.';
                            @endphp
                            <div class="notification-row border rounded-3 p-3 {{ $isUnread ? 'notification-row-unread' : 'bg-white' }}">
                                <div class="d-flex justify-content-between gap-3 flex-wrap">
                                    <div class="d-flex gap-3 align-items-start flex-grow-1">
                                        <div class="notification-icon-shell {{ $isUnread ? 'notification-icon-unread' : '' }}">
                                            <i class="mdi mdi-bell-ring-outline"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                <h6 class="mb-0">{{ $notificationTitle }}</h6>
                                                @if($isUnread)
                                                    <span class="badge bg-danger">Unread</span>
                                                @else
                                                    <span class="badge bg-light text-dark">Read</span>
                                                @endif
                                            </div>
                                            <p class="text-muted mb-2">{{ $notificationMessage }}</p>
                                            <small class="text-muted">{{ optional($notification->created_at)->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route($notificationsOpenRouteName, $notification->id) }}" class="btn btn-sm {{ $isUnread ? 'btn-primary' : 'btn-outline-primary' }}">
                                            <i class="mdi mdi-arrow-right-circle-outline me-1"></i>Open
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.notification-page-card {
    border: 0;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

.notification-row {
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    border-color: #e5e7eb !important;
}

.notification-row:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    border-color: #cbd5e1 !important;
}

.notification-row-unread {
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    border-color: #fdba74 !important;
}

.notification-icon-shell {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eef2ff;
    color: #334155;
    font-size: 1.2rem;
}

.notification-icon-unread {
    background: linear-gradient(135deg, #ef4444, #f97316);
    color: #ffffff;
}
</style>
@endsection