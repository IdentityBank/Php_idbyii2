function verificationCharacterAction(event) {

    if (event.which == 13) {
        event.preventDefault();
        return;
    }

    var $this = $(event.target);
    var index = parseFloat($this.attr('data-index'));
    let key = Number(event.key);

    if (isNaN(key)) {
        let currentValue = $this.val();
        $this.val("");
        if ((event.key == 'Backspace')
            && (index > 10)) {
            if (!currentValue) {
                $('[data-index="' + (index - 1).toString() + '"]').focus();
                $('[data-index="' + (index - 1).toString() + '"]').val("");
            }
        }
    } else {
        $this.val(key);
        if (index == 15) {
            if ($("#next").length) {
                $('#next').focus();
            }
        } else {
            $('[data-index="' + (index + 1).toString() + '"]').focus();
        }
    }
}

$(function () {
    $('#id-verification').keyup(function (event) {
        verificationCharacterAction(event);
    });
    $('[data-index="10"]').focus();
});
