<!-- auth.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Verse | Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
        }

        .card-wall-bg {
            position: absolute; top: -20%; left: -20%; width: 140%; height: 140%;
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px;
            transform: rotate(-10deg); z-index: 0; opacity: 0.15;
            mask-image: radial-gradient(circle, transparent 20%, black 100%);
            -webkit-mask-image: radial-gradient(circle at center, rgba(0,0,0,0) 10%, rgba(0,0,0,1) 70%);
        }

        .wall-column { display: flex; flex-direction: column; gap: 20px; }
        .wall-img {
            width: 100%; height: 280px;
            background-size: cover; background-position: center;
            border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            transition: transform 0.3s;
        }
        .wall-img:hover { transform: translateY(-8px); }

        .auth-content {
            position: relative; z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 450px;
            width: 100%;
            padding: 0 20px;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            width: 100%;
            transition: transform 0.3s;
        }

        .form-card:hover {
            transform: translateY(-5px);
        }

        .form-card h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 30px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-card input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-card input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-card button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .form-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .auth-toggle {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .auth-toggle a {
            color: var(--accent-blue);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .auth-toggle a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .form-hidden {
            display: none;
        }

        .form-active {
            display: block;
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 30px 20px;
            }
            
            .form-card h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <nav class="nav-dock">
        <div class="nav-item" onclick="window.location.href='index.php'" title="Inicio">🏠</div>
        <div class="nav-item active" title="Acceso">👤</div>
    </nav>

    <div class="auth-container">
        <div class="card-wall-bg">
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/1_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/2_hires.png')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/4_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/5_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/7_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/8_hires.png')"></div>
            </div>
            <div class="wall-column col-down">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/10_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/11_hires.png')"></div>
            </div>
            <div class="wall-column col-up">
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/13_hires.png')"></div>
                <div class="wall-img" style="background-image: url('https://images.pokemontcg.io/swsh1/14_hires.png')"></div>
            </div>
        </div>

        <div class="auth-content">
            <!-- FORMULARIO LOGIN -->
            <div id="login-form" class="form-card form-active">
                <h2>🔐 Iniciar Sesión</h2>
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    <button type="submit">Iniciar Sesión</button>
                </form>
                <div class="auth-toggle">
                    <p>¿No tienes cuenta? <a href="#" onclick="toggleForm('register')">Regístrate aquí</a></p>
                </div>
            </div>

            <!-- FORMULARIO REGISTRO -->
            <div id="register-form" class="form-card form-hidden">
                <h2>✨ Registrarse</h2>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Nombre completo" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    <button type="submit">Registrarse</button>
                </form>
                <div class="auth-toggle">
                    <p>¿Ya tienes cuenta? <a href="#" onclick="toggleForm('login')">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(formType) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            
            if (formType === 'register') {
                loginForm.classList.remove('form-active');
                loginForm.classList.add('form-hidden');
                registerForm.classList.remove('form-hidden');
                registerForm.classList.add('form-active');
            } else {
                registerForm.classList.remove('form-active');
                registerForm.classList.add('form-hidden');
                loginForm.classList.remove('form-hidden');
                loginForm.classList.add('form-active');
            }
        }
    </script>

</body>
</html>