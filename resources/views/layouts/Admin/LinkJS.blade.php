<!-- Core JS -->
<!-- build:js dashboard_assets/assets/vendor/js/core.js -->
<script src="{{ asset('dashboard_assets/assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('dashboard_assets/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('dashboard_assets/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('dashboard_assets/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>

<script src="{{ asset('dashboard_assets/assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('dashboard_assets/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset('dashboard_assets/assets/js/main.js') }}"></script>

<!-- Page JS -->
<script src="{{ asset('dashboard_assets/assets/js/dashboards-analytics.js') }}"></script>

<!-- Page Navigation Spinner Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners to all links and form submits
        const allLinks = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript:"])');
        const allForms = document.querySelectorAll('form');
        const spinnerOverlay = document.getElementById('spinnerOverlay');
        
        // Function to show spinner
        function showSpinner() {
            if (spinnerOverlay) {
                spinnerOverlay.style.display = 'flex';
            }
        }
        
        // Add click event to links
        allLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Don't show spinner for same-page anchors or javascript: links
                if (this.getAttribute('href') && 
                    !this.getAttribute('href').startsWith('#') && 
                    !this.getAttribute('href').startsWith('javascript:')) {
                    showSpinner();
                }
            });
        });
        
        // Add submit event to forms
        allForms.forEach(form => {
            form.addEventListener('submit', function() {
                showSpinner();
            });
        });
        
        // Also show spinner when using browser back/forward buttons
        window.addEventListener('beforeunload', function() {
            showSpinner();
        });
    });
</script>

<!-- Place this tag in your head or just before your close body tag. -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
