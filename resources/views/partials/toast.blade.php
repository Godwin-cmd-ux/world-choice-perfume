{{-- Centered confirm + toast component --}}
<div id="toastConfirm" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="toast-dismiss absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>
    <div class="relative w-full max-w-sm rounded-2xl bg-white px-6 py-8 text-center shadow-2xl">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
            <i class="fas fa-exclamation-triangle text-xl text-red-500"></i>
        </div>
        <p id="toastConfirmMessage" class="text-sm font-medium text-gray-800"></p>
        <div class="mt-6 flex gap-3">
            <button type="button" id="toastConfirmCancel" class="flex-1 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-200">Cancel</button>
            <button type="button" id="toastConfirmOk" class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">Confirm</button>
        </div>
    </div>
</div>

<div id="toastMessage" class="pointer-events-none fixed left-1/2 top-1/2 z-[110] -translate-x-1/2 -translate-y-1/2 hidden">
    <div class="flex items-center gap-3 rounded-xl bg-gray-900/95 px-5 py-3 text-sm font-medium text-white shadow-2xl">
        <i id="toastMessageIcon" class="fas fa-info-circle text-blue-400"></i>
        <span id="toastMessageText"></span>
    </div>
</div>

<script>
(function () {
    const confirmBox = document.getElementById('toastConfirm');
    const confirmMessage = document.getElementById('toastConfirmMessage');
    const confirmOk = document.getElementById('toastConfirmOk');
    const confirmCancel = document.getElementById('toastConfirmCancel');
    let pendingAction = null;

    function openConfirm(message, onConfirm) {
        confirmMessage.textContent = message;
        pendingAction = onConfirm;
        confirmOk.disabled = false;
        confirmBox.classList.remove('hidden');
    }

    function closeConfirm() {
        confirmBox.classList.add('hidden');
        pendingAction = null;
    }

    confirmOk.addEventListener('click', function () {
        if (pendingAction) {
            const action = pendingAction;
            pendingAction = null;
            closeConfirm();
            action();
        }
    });

    confirmCancel.addEventListener('click', closeConfirm);

    confirmBox.addEventListener('click', function (e) {
        if (e.target.classList.contains('toast-dismiss')) closeConfirm();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !confirmBox.classList.contains('hidden')) closeConfirm();
    });

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-confirm]');
        if (!trigger) return;
        e.preventDefault();
        e.stopPropagation();
        const message = trigger.getAttribute('data-confirm') || 'Are you sure?';
        openConfirm(message, function () {
            const form = trigger.closest('form');
            if (form) {
                form.submit();
            } else if (trigger.tagName === 'A') {
                window.location.href = trigger.href;
            } else {
                trigger.click();
            }
        });
    });

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form || !form.getAttribute) return;
        const message = form.getAttribute('data-confirm');
        if (!message) return;
        e.preventDefault();
        openConfirm(message, function () {
            form.submit();
        });
    });

    const toastBox = document.getElementById('toastMessage');
    const toastText = document.getElementById('toastMessageText');
    const toastIcon = document.getElementById('toastMessageIcon');
    let toastTimer = null;

    window.wcpToast = {
        show: function (message, type) {
            const icons = {
                success: 'fa-check-circle text-emerald-400',
                error: 'fa-exclamation-circle text-red-400',
                warning: 'fa-exclamation-triangle text-amber-400',
                info: 'fa-info-circle text-blue-400'
            };
            toastIcon.className = 'fas ' + (icons[type] || icons.info);
            toastText.textContent = message;
            toastBox.classList.remove('hidden');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toastBox.classList.add('hidden');
            }, 3500);
        },
        confirm: function (message, onConfirm) {
            openConfirm(message, onConfirm);
        }
    };
})();
</script>