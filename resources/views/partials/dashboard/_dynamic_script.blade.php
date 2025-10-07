<script>
(function($) {
    'use strict';
    
    $(document).ready(function() {
        $('.select2js').each(function() {
            const $select = $(this);
            const options = { width: '100%' };
            const parentSelector = $select.data('dropdownParent');

            if (parentSelector) {
                const $parent = $(parentSelector);
                if ($parent.length) {
                    options.dropdownParent = $parent;
                }
            } else {
                const $modalParent = $select.closest('.modal');
                if ($modalParent.length) {
                    options.dropdownParent = $modalParent;
                }
            }

            $select.select2(options);
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if($('.datetimepicker').length > 0){
            flatpickr('.datetimepicker', {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
            });
        }

        if($('.datepicker').length > 0){
            flatpickr('.datepicker', {
                enableTime: false,
                dateFormat: 'Y-m-d',
            });
        }

        if($('.maxdatepicker').length > 0){
            flatpickr('.maxdatepicker', {
                minDate: "today",
                enableTime: false,
                dateFormat: 'Y-m-d',
            });
        }

        if($('.timepicker24').length > 0){
            flatpickr('.timepicker24', {
                enableTime: true,
                noCalendar: true,
                time_24hr: true,
                dateFormat: "H:i:S",
            });
        }

        function errorMessage(message) {
            Swal.fire({
                icon: 'error',
                title: "{{ __('message.opps') }}",
                text: message,
                confirmButtonColor: "var(--bs-primary)",
                confirmButtonText: "{{ __('message.ok') }}"
            });
        }

        function showMessage(message) {
            Swal.fire({
                icon: 'success',
                title: "{{ __('message.done') }}",
                text: message,
                confirmButtonColor: "var(--bs-primary)",
                confirmButtonText: "{{ __('message.ok') }}"
            });
        }

        function submitAjaxForm(form, trigger, extraData = {}) {
            $('form').validator('update');

            const method = form.attr('method') || 'POST';
            const url = form.attr('action');
            const formData = new FormData(form[0]);

            Object.keys(extraData).forEach(function (key) {
                formData.append(key, extraData[key]);
            });

            trigger.addClass('disabled');

            $.ajax({
                type: method,
                url: url,
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(e) {
                    trigger.removeClass('disabled');

                    if (e.event === 'confirm') {
                        Swal.fire({
                            icon: 'warning',
                            title: "{{ __('message.please_confirm') }}",
                            text: e.message,
                            showCancelButton: true,
                            confirmButtonColor: "var(--bs-primary)",
                            cancelButtonColor: "#6c757d",
                            confirmButtonText: e.confirm_label || "{{ __('message.ok') }}",
                            cancelButtonText: e.cancel_label || "{{ __('message.cancel') }}"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitAjaxForm(form, trigger, Object.assign({}, extraData, { force_confirm: 1 }));
                            }
                        });
                        return;
                    }

                    if (e.status == true) {
                        if (e.event == "submited") {
                            showMessage(e.message);
                            $(".modal").modal('hide');
                            $('.dataTable').DataTable().ajax.reload( null, false );
                        }
                        if(e.event == 'refresh'){
                            window.location.reload();
                        }
                        if(e.event == "callback"){
                            showMessage(e.message);
                            $(".modal").modal('hide');
                            location.reload();
                        }
                        if(e.event == 'norefresh') {
                            showMessage(e.message);
                            $(".modal").modal('hide');
                            getAssignList(e.type);
                        }
                    }

                    if (e.status == false && e.event == 'validation') {
                        errorMessage(e.message);
                    }
                },
                error: function() {
                    trigger.removeClass('disabled');
                }
            });
        }

        $(document).on('click', '[data-form="ajax"]', function(event) {
            event.preventDefault();
            const trigger = $(this);
            const form = trigger.closest('form');

            if (! form.length) {
                return;
            }

            submitAjaxForm(form, trigger);
        });

        $(document).on('change','.change_status', function() {

            var status = $(this).prop('checked') == true ? 1 : 0;
            
            var key_name = $(this).attr('data-name');
            var id = $(this).attr('data-id');
            var type = $(this).attr('data-type');
            $.ajax({
                type: "GET",
                dataType: "json",
                url: "{{ route('changeStatus') }}",
                data: { 'status': status, 'id': id ,'type': type ,[key_name]: key_name },
                success: function(data){
                    if(data.status == false){
                        errorMessage(data.message)
                    }else{
                        showMessage(data.message);
                    }
                }
            });
        })

        $(document).on('click', '[data-toggle="tabajax"]', function(e) {
            e.preventDefault();
            var selectDiv = this;
            ajaxMethodCall(selectDiv);
        });
        
        function ajaxMethodCall(selectDiv) {

            var $this = $(selectDiv),
                loadurl = $this.attr('data-href'),
                targ = $this.attr('data-target'),
                id = selectDiv.id || '';

            $.post(loadurl, function(data) {
                $(targ).html(data);
                $('form').append('<input type="hidden" name="active_tab" value="'+id+'" />');
            });

            $this.tab('show');
            return false;
        }

        $('form[data-toggle="validator"]').on('submit', function (e) {
            window.setTimeout(function () {
                var errors = $('.has-error')
                if (errors.length) {
                    $('html, body').animate({ scrollTop: "0" }, 500);
                    e.preventDefault()
                }
            }, 0);
        });   
        
        $(document).on('click','[data--confirmation="true"]',function(e){
            e.preventDefault();
            var form = $(this).attr('data--submit');

            var title = $(this).attr('data-title');

            var message = $(this).attr('data-message');

            var ajaxtype = $(this).attr('data--ajax');
            if(form == 'confirm_form') {
                $('#confirm_form').attr('action', $(this).attr('href'));
            }
            let __this = this

            confirmation(form,title,message,ajaxtype,__this);
        });

        function confirmation(form,title = "{{ __('message.confirmation') }}",message = "{{ __('message.delete_msg') }}",ajaxtype=false,_this) 
        {
            const storageDark = localStorage.getItem('theme');
            const theme = (storageDark == "light") ? 'material' : 'dark';
            $.confirm({
                content: message,
                type: '',
                title: title,
                buttons: {
                    yes: {
                        action: function () {
                            
                            if(ajaxtype == 'true') {
                                let url = _this;

                                let data = $('[data--submit="'+form+'"]').serializeArray();
                                $.post(url, data).then(response => {
                                    if(response.status) {
                                        if(response.event == 'norefresh') {
                                            getAssignList(response.type);
                                        }
                                        if(response.image != null){
                                            $(_this).remove();
                                            $('#'+response.preview).attr('src',response.image)
                                            if (jQuery.inArray(response.preview, ["service_attachment_preview"]) !== -1) {
                                                $('#'+response.preview+"_"+response.id).remove()
                                                let total_file = $('.remove-file').length;
                                                if(total_file == 0){
                                                    $('.service_attachment_div').remove();
                                                }
                                            }
                                            if(response.preview == 'site_logo_preview'){
                                                $('.'+response.preview).attr('src',response.image);
                                            }
                                            if(response.preview == 'site_favicon_preview'){
                                                $('.'+response.preview).attr('href',response.image);
                                            }

                                            if(response.preview == 'site_dark_logo_preview'){
                                                $('.'+response.preview).attr('src',response.image);
                                            }

                                            showMessage(response.message)
                                            return true;
                                        }
                                        $('.dataTable').DataTable().ajax.reload( null, false );
                                        showMessage(response.message)
                                    }
                                    if(response.status == false){
                                        errorMessage(response.message)
                                    }
                                })
                            } else {
                                if (form !== undefined && form){
                                    $(document).find('[data--submit="'+form+'"]').submit();
                                }else{
                                    return true;
                                }
                            }
                        }
                    },
                    no: {
                        action: function () {}
                    },
                },
                theme: theme
            });
            return false;
        }
        $(document).on('change', '.file-input', function() {
            readURL(this);
        })
        $(".file-upload").on('change', function(){
            readURL(this);
        });

        $(".upload-button").on('click', function() {
            $(".file-upload").click();
        });

        $(document).on('change', '.custom-file-input', function() {
            readURL(this);
        })

        function readURL(input) {
            var target = $(input).attr('data--target');
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                var field_name = $(input).attr('name');
                var msg = "{{ __('message.image_png_jpg') }}";
                
                if (jQuery.inArray(field_name, ['exercise_video']) !== -1) {
                    res = isVideoAttachments(input.files[0].name);

                    if(res == false) {
                        var msg = __('message.files_not_allowed');
                        $(input).val("");
                        flag = false;
                    }
                } else {
                    var res = isImage(input.files[0].name);
                }

                if (res == false) {
                    errorMessage(msg)
                    $(input).val("");
                    return false;
                }
                reader.onload = function(e) {
                    $('.'+target).attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }

            var modal = $(input).attr('data--modal');

            if (modal !== undefined && modal !== null && modal === 'modal')
                $('.image_upload-modal').modal('hide');

        }

        function getExtension(filename) {
            var parts = filename.split('.');
            return parts[parts.length - 1];
        }

        function isImage(filename) {
            var ext = getExtension(filename);
            switch (ext.toLowerCase()) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                case 'svg':
                case 'ico':
                    return true;
            }
            return false;
        }

        function isVideoAttachments(filename) {
            var ext = getExtension(filename);
            var validExtensions = [ 'mp4', 'avi', 'mkv', '3gp', 'wmv', 'mov', 'flv' ];

            if (jQuery.inArray(ext.toLowerCase(), validExtensions) !== -1) {
                return true;
            }
            return false;
        }


        function isDocuments(filename) {
            var ext = getExtension(filename);
            var validExtensions = ['jpg', 'pdf', 'jpeg', 'gif', 'png'];

            if (jQuery.inArray(ext.toLowerCase(), validExtensions) !== -1) {
                return true;
            }
            return false;
        }

        function isAttachments(filename) {
            var ext = getExtension(filename);
            var validExtensions = ['jpg', 'pdf', 'jpeg', 'gif', 'png', 'mp4', 'avi'];
            
            if (jQuery.inArray(ext.toLowerCase(), validExtensions) !== -1) {
                return true;
            }
            return false;
        }

        //PHONE 
        var input = document.querySelector("#number");
        var errorMsg = document.querySelector("#error-msg");
        var validMsg = document.querySelector("#valid-msg");

        if (input) {
            var iti = window.intlTelInput(input, {
                hiddenInput: "contact_number",
                separateDialCode: true,
                utilsScript: "{{ asset('vendor/intlTelInput/js/utils.js') }}"
            });

            input.addEventListener("countrychange", function() {
                validate();
            });

            var errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

            const phone = $('#number');
            const err = $('#error-msg');
            const succ = $('#valid-msg');

            var reset = function() {
                err.addClass('d-none');
                succ.addClass('d-none');
                validate();
            };

            // Ensure correct phone number format on blur
            $(document).on('blur keyup', '#number', function() {
                reset();
                var val = $(this).val();
                if (val.match(/[^0-9\.\+.\s.]/g)) {
                    $(this).val(val.replace(/[^0-9\.\+.\s.]/g, ''));
                }
                if (val === '') {
                    $('[type="submit"]').removeClass('disabled').prop('disabled', false);
                }
            });

            input.addEventListener('change', reset);
            input.addEventListener('keyup', reset);

            var errorCode = '';

            function validate() {
                if (input.value.trim()) {
                    if (iti.isValidNumber()) {
                        succ.removeClass('d-none');
                        err.html('');
                        err.addClass('d-none');
                        $('[type="submit"]').removeClass('disabled').prop('disabled', false);
                    } else {
                        errorCode = iti.getValidationError();
                        err.html(errorMap[errorCode]);
                        err.removeClass('d-none');
                        phone.closest('.form-group').addClass('has-danger');
                        $('[type="submit"]').addClass('disabled').prop('disabled', true);
                    }
                }
            }

            // **Update hidden input before form submission**
            $('form').on('submit', function() {
                var fullNumber = iti.getNumber(); // Get full number with country code
                $('input[name="phone_number"]').val(fullNumber);
            });
        }
    });
})(jQuery);
</script>