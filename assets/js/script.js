document.addEventListener(
    "DOMContentLoaded",
    function () {

        const forms =
            document.querySelectorAll(
                "form[data-confirm]"
            );

        forms.forEach(function (form) {

            form.addEventListener(
                "submit",
                function (event) {

                    const message =
                        form.dataset.confirm;

                    if (!confirm(message)) {
                        event.preventDefault();
                    }

                }
            );

        });

    }
);