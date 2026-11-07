<footer class="footer-container">
    <style>

        /* Apply font specifically to footer elements */
        footer.footer-container, 
        footer.footer-container * {
            font-family: 'DM Sans', sans-serif !important;
        }

        /* Base Desktop Styling */
        footer.footer-container {
            padding: 20px 0;
            width: 100%;
            background-color: var(--cpu-primary);
            color: var(--cpu-white);
        }

        .footer-copyright {
            color: var(--cpu-white);
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            /* Space between copyright and links */
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: var(--cpu-secondary);
            /* Gold accent on hover */
        }

        /* Mobile Styles */
        @media (max-width: 768px) {
            footer.footer-container {
                padding: 15px 0;
                background-color: #f8f9fa;
                border-top: 1px solid #dee2e6;
            }

            footer.footer-container .container {
                padding-left: 20px;
                padding-right: 20px;
            }

            .footer-copyright {
                color: #6c757d !important;
                font-size: 0.85rem;
            }

            .footer-link {
                color: #6c757d;
                font-size: 0.8rem;
                display: inline-block;
                margin-bottom: 5px;
            }
        }

        /* Extra Small Devices */
        @media (max-width: 480px) {
            footer.footer-container {
                padding: 12px 0;
            }

            .footer-link {
                margin-right: 10px !important;
                /* Tighter spacing on small phones */
            }
        }
    </style>
    <div class="container">
        <div class="row gy-3 text-center">

            <!-- Top: Copyright (Centered) -->
            <div class="col-12">
                <p class="mb-0 footer-copyright">&copy; 2025 Central Philippine University
            </div>

            <!-- Bottom: Quick Links (Centered) -->
            <div class="col-12">
                <a href="{{ url('/privacy-policy') }}" class="text-decoration-none me-3 footer-link">Privacy Policy</a>
                <a href="{{ url('/terms-of-use') }}" class="text-decoration-none me-3 footer-link">Terms of Use</a>
                <a href="{{ url('/contact-support') }}" class="text-decoration-none footer-link">Contact Support</a>
            </div>

        </div>
    </div>
</footer>