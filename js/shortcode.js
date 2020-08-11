jQuery(document).ready(function ($) {

    $("#whatapp-form").submit(function (e) {
        e.preventDefault();
        var fullTelephone = $('#wp-phone-message-full-phone-number').val();
        var message = $('#wp-phone-message-message').val();
        var name = $('#wp-phone-message-name').val();
        var address = $('#wp-phone-message-address').val();
        var phone = $('#wp-phone-message-phone').val();
        var email = $('#wp-phone-message-email').val();
        var title = $('#wp-phone-message-title').val();

        var day = $("#timepicker__day option:selected").text();
        var time = $('#timepicker__time').val();

        if (whatappValidation(fullTelephone, 'whatapp-error')) {
            final_message = whatappCreateFinalMessage(name, address, phone, email, day, time, message);
            var whatappUrl = "https://wa.me/" + fullTelephone + "?text=" + final_message;

            popupwindow(whatappUrl, title, 1000, 700);
        }
        return false;
    });

    $("#whatapp-widget-form").submit(function (e) {
        e.preventDefault();
        var fullTelephone = $('#wp-phone-message-widget-full-phone-number').val();
        var message = $('#wp-phone-message-widget-message').val();
        var name = $('#wp-phone-message-widget-name').val();
        var address = $('#wp-phone-message-widget-address').val();
        var phone = $('#wp-phone-message-widget-phone').val();
        var email = $('#wp-phone-message-widget-email').val();

        var day = $('#timepicker__day').val();
        var time = $('#timepicker__time').val();

        if (whatappValidation(fullTelephone, 'whatapp-widget-error')) {
            final_message = whatappCreateFinalMessage(name, address, phone, email, day, time, message);
            var whatappUrl = "https://wa.me/" + fullTelephone + "?text=" + final_message;

            popupwindow(whatappUrl, 'Whatsapp Me', 1000, 700);
        }
        return false;
    });

    function popupwindow(url, title, w, h) {
        var left = (screen.width / 2) - (w / 2);
        var top = (screen.height / 2) - (h / 2);
        return window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
    }

    function whatappValidation(fullTelephone, errorTarget) {
        if (fullTelephone && fullTelephone != '0') {
            whatappErrorMessage(" ", errorTarget);
            return true;
        } else {
            whatappErrorMessage("Telephone number is not set or not valid.", errorTarget);
            return false;
        }
    }

    function whatappErrorMessage(errorMessage, errorTarget) {
        $("#" + errorTarget).text(errorMessage);
    }

    function whatappCreateFinalMessage(name, address, phone, email, day, time, message) {

        final_message = '';
            final_message += 'NOVA COMANDA (FINSARA) %0a';
        if (name !== undefined)
            final_message += 'Nom de l\'establiment: ' + name + ' %0a';
        if (address !== undefined)
            final_message += 'Nom i cognoms: ' + address + ' %0a';
        if (phone !== undefined)
            final_message += 'Número de telèfon: ' + phone + ' %0a';
        if (email !== undefined)
            final_message += 'Email: ' + email + ' %0a';
        if (day !== undefined)
            final_message += 'Hora de recollida: ' + day + ' - ' + time + ' %0a';
            final_message += 'Productes: %0a';
        final_message += message.replace(/[\r\n]/g, " %0a");

        return final_message;
    }

});