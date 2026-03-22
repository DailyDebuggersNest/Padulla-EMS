            </div> <!-- container-fluid end -->
        </div> <!-- page-content-wrapper end -->
    </div> <!-- wrapper end -->

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery for AJAX (optional, but helps with quick functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom JS -->
    <script src="<?= BASE_PATH ?>assets/js/script.js"></script>
    <script>
        // Toggle Sidebar
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
    </script>
</body>
</html>