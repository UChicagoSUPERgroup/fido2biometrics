    <footer id="footer">
        <div class="container" style="padding-top: 15px;">
            <div class="row">
                <div class="col-sm-12">
                    <p class="text-muted" style="font-size: 0.7rem;">&copy; Example Tech 2021</p>
                </div>
            </div>
        </div>
    </footer>
    <script>
        // Push the footer down
        $(document).ready(function () {
            'use strict';
            var docHeight = $(window).height();
            var footerHeight = $('#footer').height();
            var footerTop = $('#footer').position().top + footerHeight;
            if (footerTop < docHeight) {
                $('#footer').css('margin-top', 0 + (docHeight - footerTop) + 'px');
            }
        });
    </script>
    </body>
</html>