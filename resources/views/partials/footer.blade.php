<footer class="footer-container">
    <style>
        /* Original Desktop Footer Styles - Keep exactly as was */
        .footer-container {
            /* No background color added - keeps original */
            padding: 0; /* Original padding */
            width: 100%;
        }

        .footer-container p {
            margin: 0;
            /* Original text color preserved */
        }

        /* MOBILE RESPONSIVE STYLES ONLY */
        @media (max-width: 768px) {
            .footer-container {
                padding: 15px 0; /* Add padding for mobile */
                background-color: #f8f9fa; /* Light background for visibility on mobile */
                border-top: 1px solid #dee2e6;
            }

            .footer-container .container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .footer-container p {
                font-size: 0.8rem;
                line-height: 1.5;
                color: #6c757d; /* Visible color for mobile */
            }
        }

        /* Extra small devices */
        @media (max-width: 480px) {
            .footer-container {
                padding: 12px 0;
            }

            .footer-container p {
                font-size: 0.75rem;
                padding: 0 10px;
            }
        }

        /* Ensure footer is visible at bottom - Add this for structure */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1 0 auto;
        }

        .footer-container {
            flex-shrink: 0;
        }
    </style>
    <div class="container text-center">
        <p class="mb-0">&copy; 2025 Central Philippine University | All Rights Reserved</p>
    </div>
</footer>