<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

include 'db.php';

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT name, email, username, created_at, avatar_url FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCG Verse | Mi Perfil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .profile-container {
            position: relative;
            min-height: 100vh;
            background: var(--bg-main);
        }

        .profile-hero {
            position: relative;
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0 20px;
            background: #fff;
        }

        .profile-content {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px 60px 80px;
        }

        .profile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
            margin-bottom: 40px;
            transition: transform 0.3s;
        }

        .profile-header:hover {
            transform: translateY(-5px);
        }

        .avatar-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #0f172a;
            letter-spacing: -1px;
        }

        .profile-username {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }

        .profile-stats {
            display: flex;
            justify-content: center;
            gap: 60px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--accent-blue);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-top: 5px;
            font-weight: 600;
        }

        .profile-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s;
        }

        .profile-section:hover {
            transform: translateY(-5px);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 25px;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .btn-edit {
            background: #0f172a;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 25px;
            font-size: 1rem;
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .empty-state small {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(15, 23, 42, 0.8);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            margin: auto;
            padding: 28px 32px;
            border-radius: 20px;
            width: 90%;
            max-width: 420px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.18);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .close-button {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bg-main);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 600;
        }

        .close-button:hover {
            background: #f1f5f9;
            color: var(--text-primary);
            transform: scale(1.1);
        }

        .modal h2 {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: #0f172a;
            letter-spacing: -0.025em;
            text-align: center;
            padding-right: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            transition: all 0.2s ease;
            background: #f8fafc;
            color: var(--text-primary);
            box-sizing: border-box;
            font-weight: 500;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .modal .btn-main {
            width: 100%;
            margin-top: 4px;
            padding: 12px 20px;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 10px;
            letter-spacing: 0.025em;
            transition: all 0.2s ease;
        }

        .modal .btn-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
        }

        .modal.show {
            display: flex;
        }

        @media (max-width: 768px) {
            .modal-content {
                padding: 20px 16px;
                margin: 16px;
                max-width: calc(100% - 32px);
            }
            
            .modal h2 {
                font-size: 1.25rem;
                margin-bottom: 16px;
                padding-right: 15px;
            }
            
            .form-group {
                margin-bottom: 14px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .modal .btn-main {
                padding: 10px 16px;
                font-size: 0.9rem;
                margin-top: 2px;
            }
        }

        @media (max-width: 768px) {
            .profile-content {
                padding: 20px 15px 40px 15px;
            }
            
            .profile-header {
                padding: 30px 20px;
            }
            
            .profile-stats {
                gap: 30px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-sections {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-section {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>

    <nav class="nav-dock">
        <div class="nav-item" onclick="window.location.href='index.php'" title="Inicio">🏠</div>
        <div class="nav-item" onclick="switchGame('pokemon')" title="Pokémon">⚡</div>
        <div class="nav-item" onclick="switchGame('yugioh')" title="Yu-Gi-Oh!">👁️</div>
        <div class="nav-item" onclick="switchGame('magic')" title="Magic">🔥</div>
        <div class="nav-item" onclick="switchGame('onepiece')" title="One Piece">☠️</div>
        
        <div style="flex-grow: 1;"></div>
        
        <div class="nav-item active" title="Perfil">👤</div>
        <a href="logout.php" class="nav-item" title="Cerrar Sesión" style="color:#ef4444; text-decoration:none;">🚪</a>
    </nav>

    <div class="profile-container">
        <div class="profile-hero">
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

            <div class="hero-content">
                <div class="profile-header">
                    <div class="avatar-container">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Cartas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Colecciones</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Intercambios</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-sections">
                <div class="profile-section">
                    <h3 class="section-title">📋 Información Personal</h3>
                    <div class="info-item">
                        <span class="info-label">Nombre</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Usuario</span>
                        <span class="info-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Miembro desde</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <button class="btn-edit" onclick="openEditModal()">✏️ Editar Perfil</button>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">🎯 Actividad Reciente</h3>
                    <div class="empty-state">
                        <div class="empty-state-icon">📚</div>
                        <p>Aún no tienes actividad</p>
                        <small>Comienza explorando el catálogo de cartas</small>
                    </div>
                </div>

                <div class="profile-section">
                    <h3 class="section-title">⭐ Favoritos</h3>
                    <div class="empty-state">
                        <div class="empty-state-icon">💝</div>
                        <p>No tienes cartas favoritas</p>
                        <small>Añade cartas a tus favoritos para verlas aquí</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <button class="close-button" onclick="closeEditModal()" aria-label="Cerrar">×</button>
            <h2>Editar Perfil</h2>
            <form id="editProfileForm">
                <div class="form-group">
                    <label for="editName">Nombre</label>
                    <input type="text" id="editName" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editUsername">Usuario</label>
                    <input type="text" id="editUsername" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="btn-main full-width">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById('editProfileModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editProfileModal').classList.remove('show');
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editProfileModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Manejar envío del formulario
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            fetch('update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    closeEditModal();
                    // Recargar la página para mostrar los cambios
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el perfil. Inténtalo de nuevo.');
            });
        });
    </script>

</body>
</html>
