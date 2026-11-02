<?php
/*
 * adminFooter.php
 * ---------------------------------------------------------------------
 * Shared M-LINK admin FOOTER + sidebar toggle script.
 * Closes the <div class="main-wrapper"> and <div class="dashboard-container">
 * that adminHeader.php opened, so include it after the page content.
 * ---------------------------------------------------------------------
 */
?>

        <!-- =====================================
             FOOTER
        ====================================== -->
        <footer class="site-footer">
            <p>
                &copy; <?php echo date("Y"); ?>
                M-LINK | Empowering Communities
            </p>
        </footer>

    </div><!-- /.main-wrapper -->

</div><!-- /.dashboard-container -->

<!-- SIDEBAR TOGGLE (same behaviour as the admin dashboard) -->
<script>
document.addEventListener(
    "DOMContentLoaded",
    function () {

        const menuBtn =
            document.getElementById("menuBtn");

        const sidebar =
            document.getElementById("sidebar");

        const mainWrapper =
            document.getElementById("mainWrapper");

        if (!menuBtn || !sidebar) {
            return;
        }

        menuBtn.addEventListener(
            "click",
            function () {

                if (window.innerWidth <= 750) {

                    sidebar.classList.toggle("mobile-open");

                } else {

                    sidebar.classList.toggle("collapsed");

                    if (mainWrapper) {
                        mainWrapper.classList.toggle("sidebar-collapsed");
                    }

                }

            }
        );

        window.addEventListener(
            "resize",
            function () {

                if (window.innerWidth > 750) {
                    sidebar.classList.remove("mobile-open");
                }

            }
        );

    }
);
</script>