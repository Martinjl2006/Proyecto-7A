<?php
// Asegurarse de que no haya sesión activa
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuenta Eliminada - LeyendAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animación de fondo */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(50px, 50px);
            }
        }

        /* Contenedor principal */
        .container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Icono principal */
        .icon-container {
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out 0.2s backwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .main-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            box-shadow: 0 10px 30px rgba(255, 107, 107, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        /* Títulos y texto */
        .title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            animation: fadeIn 0.8s ease-out 0.3s backwards;
        }

        .subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
            animation: fadeIn 0.8s ease-out 0.4s backwards;
        }

        /* Lista de elementos eliminados */
        .deleted-items {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            animation: fadeIn 0.8s ease-out 0.5s backwards;
        }

        .deleted-items h3 {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .deleted-items ul {
            list-style: none;
            padding: 0;
        }

        .deleted-items li {
            padding: 10px 0;
            color: #555;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .deleted-items li:last-child {
            border-bottom: none;
        }

        .deleted-items li i {
            color: #ff6b6b;
            font-size: 1.2rem;
        }

        /* Mensaje de despedida */
        .farewell-message {
            background: linear-gradient(135deg, #e8eeff, #f0f4ff);
            border-left: 4px solid #667eea;
            border-radius: 12px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
            color: #555;
            line-height: 1.6;
            animation: fadeIn 0.8s ease-out 0.6s backwards;
        }

        .farewell-message strong {
            color: #333;
            display: block;
            margin-bottom: 10px;
        }

        /* Botones */
        .buttons-container {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            animation: fadeIn 0.8s ease-out 0.7s backwards;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            padding: 15px 30px;
            border-radius: 25px;
            border: none;
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f0f4ff;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 0.9rem;
            animation: fadeIn 0.8s ease-out 0.8s backwards;
        }

        /* Logo */
        .logo-container {
            margin-bottom: 20px;
            animation: fadeIn 0.8s ease-out 0.1s backwards;
        }

        .logo {
            height: 50px;
            opacity: 0.7;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 40px 25px;
            }

            .title {
                font-size: 1.8rem;
            }

            .subtitle {
                font-size: 1rem;
            }

            .main-icon {
                width: 100px;
                height: 100px;
                font-size: 3rem;
            }

            .buttons-container {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Confetti decorativo */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #667eea;
            opacity: 0.6;
            animation: fall 3s linear infinite;
        }

        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="logo_logo_re_logo_sin_fondo_-removebg-preview.png" alt="LeyendAR Logo" class="logo">
        </div>

        <div class="icon-container">
            <div class="main-icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>

        <h1 class="title">Cuenta Eliminada Exitosamente</h1>
        <p class="subtitle">
            Tu cuenta y todos los datos asociados han sido eliminados permanentemente de LeyendAR.
        </p>

        <div class="deleted-items">
            <h3>
                <i class="fas fa-trash-alt"></i>
                Lo siguiente ha sido eliminado:
            </h3>
            <ul>
                <li>
                    <i class="fas fa-user-times"></i>
                    <span>Tu perfil y datos personales</span>
                </li>
                <li>
                    <i class="fas fa-book"></i>
                    <span>Todos tus mitos y leyendas publicados</span>
                </li>
                <li>
                    <i class="fas fa-images"></i>
                    <span>Todas las imágenes asociadas</span>
                </li>
                <li>
                    <i class="fas fa-star"></i>
                    <span>Tus favoritos y validaciones</span>
                </li>
                <li>
                    <i class="fas fa-database"></i>
                    <span>Todo el historial de actividad</span>
                </li>
            </ul>
        </div>

        <div class="farewell-message">
            <strong>💔 Lamentamos verte partir</strong>
            Gracias por haber sido parte de la comunidad LeyendAR. Tu contribución ayudó a preservar y compartir las historias y leyendas de Argentina. 
            
            <br><br>
            
            Si en el futuro deseas volver, siempre serás bienvenido/a. Puedes crear una nueva cuenta en cualquier momento.
        </div>

        <div class="buttons-container">
            <a href="registro.html" class="btn btn-primary">
                <i class="fas fa-user-plus"></i>
                Crear nueva cuenta
            </a>
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Ir al inicio
            </a>
        </div>

        <div class="footer">
            <p>
                <i class="fas fa-info-circle"></i>
                Esta acción es irreversible. Todos tus datos han sido eliminados permanentemente.
            </p>
            <p style="margin-top: 10px;">
                © 2025 LeyendAR - Mitos y Leyendas Argentinas
            </p>
        </div>
    </div>

    <script>
        // Animación de confetti opcional
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#ff6b6b', '#f7931e'];
            for (let i = 0; i < 30; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 2 + 's';
                    confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 100);
            }
        }

        // Ejecutar al cargar la página
        window.addEventListener('load', () => {
            // Opcional: descomentar para agregar confetti
            // createConfetti();
        });

        // Prevenir volver atrás
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>