$(document).ready(function () {

    // ================================
    //  LOGIN FORM VALIDATION (Admin + Student)
    // ================================
    $("#loginForm").on("submit", function (e) {
        let user = $("#username").val().trim();
        let pass = $("#password").val().trim();

        if (user === "" || pass === "") {
            e.preventDefault();
            $("#error-box").html(
                "<div class='alert alert-danger'>Username & Password required</div>"
            );
        }
    });


    // ================================
    //  LIVE USERNAME CHECK (Registration)
    // ================================
    $("#reg_username").on("keyup", function () {
        let username = $(this).val().trim();

        if (username.length < 3) {
            $("#username-status")
                .text("Too short")
                .css("color", "orange");
            return;
        }

        $.get(
            "/Advanced_Web_Application_Project/studymate/auth/check_username.php",
            { username: username },
            function (data) {
                if (data === "taken") {
                    $("#username-status")
                        .text("Username already taken")
                        .css("color", "red");
                } else {
                    $("#username-status")
                        .text("Username available")
                        .css("color", "green");
                }
            }
        );
    });

});
