{{-- A number box must not change while someone is typing in it. Browsers
     turn a wheel event over a focused number input into an increment/decrement;
     cancelling that default keeps wheel scrolling from editing typed values. --}}
<script>
(function () {
    'use strict';

    function isNumberField(target) {
        if (!target || !target.matches) {
            return false;
        }
        return target.matches('input[type="number"], input[inputmode="numeric"], input[inputmode="decimal"]');
    }

    document.addEventListener('wheel', function (event) {
        // Only the box being typed in is protected; elsewhere the wheel still
        // scrolls the page normally.
        if (isNumberField(event.target) && document.activeElement === event.target) {
            event.preventDefault();
        }
    }, { passive: false });
})();
</script>
