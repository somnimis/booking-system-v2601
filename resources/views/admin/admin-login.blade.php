<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPU Facility Booking - Admin Login</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ============================================
           REFINED INSTITUTIONAL THEME - ADMIN LOGIN
           Matching catalog.css design system
           ============================================ */

        :root {
            --navy: #041a4b;
            --navy-mid: #0b2d72;
            --navy-light: #e8edf8;
            --amber: #f5bc40;
            --amber-dark: #d9a12a;
            --white: #ffffff;
            --surface: #f5f6fa;
            --border: #e2e6f0;
            --text-base: #1e2d4a;
            --text-muted: #6b7a99;
            --text-light: #9aaac5;
            --success: #22c55e;
            --danger: #ef4444;
            --danger-bg: #fee8e8;
            --shadow-sm: 0 1px 3px rgba(4, 26, 75, .06), 0 1px 2px rgba(4, 26, 75, .04);
            --shadow-md: 0 4px 16px rgba(4, 26, 75, .10), 0 2px 6px rgba(4, 26, 75, .06);
            --shadow-lg: 0 12px 40px rgba(4, 26, 75, .16), 0 4px 12px rgba(4, 26, 75, .08);
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --radius-xl: 24px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Allow border-radius on specific elements */
        .profile-img, .login-container, .login-container input, 
        .login-button, .home-button, .error-box {
            border-radius: var(--radius-md) !important;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-image: url("{{ asset('assets/cpu-pic1.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(4, 26, 75, 0.28) 0%, rgba(4, 26, 75, 0.75) 100%);
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            background: var(--white);
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Title Section */
        .title-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .title-container img {
            width: 70px;
            height: auto;
            margin-bottom: 1rem;
        }

        .title-container h1 {
            font-family: 'Fraunces', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--navy);
            line-height: 1.3;
            letter-spacing: -0.3px;
        }

        .login-container h2 {
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            margin-bottom: 1.75rem;
            color: var(--text-muted);
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.5px;
            position: relative;
            display: inline-block;
            padding-bottom: 0.5rem;
        }

        .login-container h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            right: 25%;
            height: 2px;
            background: var(--amber);
            border-radius: 2px;
        }

        /* Form Groups */
        .form-group {
            width: 100%;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-container input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--border);
            font-size: 0.9rem;
            font-family: 'DM Sans', sans-serif;
            transition: var(--transition);
            background: var(--white);
            color: var(--text-base);
        }

        .login-container input:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(4, 26, 75, 0.1);
        }

        .login-container input::placeholder {
            color: var(--text-light);
        }

        /* Login Button */
        .login-button {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            color: white;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 1rem;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 0.5px;
        }

        .login-button:hover {
            background: linear-gradient(135deg, var(--navy-mid) 0%, #0e3a8a 100%);
            box-shadow: var(--shadow-md);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Home Button */
        .home-button {
            width: 100%;
            padding: 0.875rem;
            background: var(--white);
            color: var(--navy);
            border: 1px solid var(--border);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            font-family: 'DM Sans', sans-serif;
            letter-spacing: 0.5px;
        }

        .home-button:hover {
            background: var(--navy-light);
            border-color: var(--navy);
            box-shadow: var(--shadow-sm);
        }

        .home-button:active {
            transform: translateY(0);
        }

        /* Error Box */
        .error-box {
            color: #991b1b;
            background-color: var(--danger-bg);
            border-left: 3px solid var(--danger);
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            display: none;
            font-size: 0.85rem;
            font-weight: 500;
            text-align: left;
        }

        /* Spinner */
        .spinner-border {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            vertical-align: middle;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }

        /* Shake Animation */
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 1.75rem;
                max-width: 420px;
            }

            .title-container h1 {
                font-size: 1rem;
            }

            .login-container h2 {
                font-size: 0.9rem;
            }

            .login-container input {
                padding: 0.75rem 0.875rem;
            }

            .login-button, .home-button {
                padding: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .login-container {
                padding: 1.5rem;
                max-width: 100%;
            }

            .title-container img {
                width: 55px;
            }

            .title-container h1 {
                font-size: 0.9rem;
            }

            .login-container h2 {
                font-size: 0.85rem;
                margin-bottom: 1.25rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .form-group label {
                font-size: 0.75rem;
            }

            .login-container input {
                padding: 0.7rem 0.875rem;
                font-size: 0.85rem;
            }

        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="title-container">
            <img src="{{ asset('assets/cpu-logo.png') }}" alt="CPU Logo">
            <h1>Central Philippine University<br>Facility and Equipment Booking Services</h1>
        </div>
        <h2>Administrator Login</h2>

        <div id="errorBox" class="error-box"></div>

        <form id="loginForm" onsubmit="return false;">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="admin@cpu.edu.ph" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" id="loginBtn" class="login-button">Login</button>
            <a href="{{ url('/home') }}" class="home-button">Return to Homepage</a>
        </form>
    </div>

    <script>
        document.getElementById('loginBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorBox = document.getElementById('errorBox');
            const loginBtn = this;
            const loginContainer = document.querySelector('.login-container');
        
            // Clear previous error
            errorBox.style.display = 'none';
            errorBox.innerHTML = '';
            loginContainer.classList.remove('shake');
        
            // Basic validation
            if (!email || !password) {
                errorBox.textContent = 'Please enter both email and password.';
                errorBox.style.display = 'block';
                loginContainer.classList.add('shake');
                setTimeout(() => loginContainer.classList.remove('shake'), 500);
                return;
            }
        
            // Disable button and show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner-border"></span> Authenticating...';
        
            try {
                const response = await fetch('/api/admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password }),
                    redirect: 'manual'
                });
        
                if (response.status === 0) {
                    throw new Error('Network error. Please check your connection.');
                }
        
                const data = await response.json();
        
                if (!response.ok) {
                    throw new Error(data.message || 'Invalid email or password.');
                }
        
                // Successful login - store token
                localStorage.setItem('adminToken', data.token);
                
                // Check user role from the login response
                const userRole = data.admin?.role;
                
                // Redirect based on role title using absolute paths from root
                if (userRole && (
                    userRole.role_title === "Vice President of Administration" || 
                    userRole.role_title === "Approving Officer"
                )) {
                    window.location.href = '/admin/signatory/dashboard';
                } else {
                    window.location.href = '/admin/dashboard';
                }
        
            } catch (error) {
                errorBox.textContent = error.message;
                errorBox.style.display = 'block';
                loginContainer.classList.add('shake');
                setTimeout(() => loginContainer.classList.remove('shake'), 500);
                console.error('Login error:', error);
            } finally {
                loginBtn.disabled = false;
                loginBtn.textContent = 'Login';
            }
        });
        
        // Allow Enter key to submit form
        document.getElementById('loginForm').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('loginBtn').click();
            }
        });
        
        // Clear error when user starts typing
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        
        function clearError() {
            const errorBox = document.getElementById('errorBox');
            errorBox.style.display = 'none';
            document.querySelector('.login-container').classList.remove('shake');
        }
        
        emailInput.addEventListener('input', clearError);
        passwordInput.addEventListener('input', clearError);
    </script>
</body>
</html>