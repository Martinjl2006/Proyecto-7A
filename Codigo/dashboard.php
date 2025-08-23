<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: inicio.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Inicio - LeyendAR</title>
<style>

:root {
  --azul-logo: #1f2f40;
  --fondo-claro: #f4f1ed;
  --acento-1: #b04c3e;   /* Rojo vino */
  --acento-2: #c7a76c;   /* Dorado suave */
}
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background-color: #f5f5f5;
  color: #333;
}

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.user-info {
  display: flex;
  align-items: center;
}

.user-info img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 1rem;
}

.menu {
  display: flex;
  gap: 2rem;
  font-weight: bold;
}

.menu a {
  text-decoration: none;
  color: #333;
}

.menu a:last-child {
  color: red;
}

main {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  padding: 2rem;
  max-width: 1200px;
  margin: auto;
}

.map-section, .myth-section {
  background-color: white;
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.map-image, .myth-content {
  background-color: #ccc;
  height: 220px;
  border-radius: 12px;
  margin-bottom: 1rem;
}

.myth-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.myth-header img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
}

.search-bar {
  margin-bottom: 1rem;
  display: flex;
  justify-content: center;
}

.search-bar input {
  width: 80%;
  padding: 0.6rem 1rem;
  border-radius: 25px;
  border: 1px solid #ccc;
  outline: none;
  transition: 0.3s;
}

.search-bar input:focus {
  border-color: #4b32c9;
  box-shadow: 0 0 5px rgba(75, 50, 201, 0.3);
}

.cards {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.card {
  background-color: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.card h4 {
  margin-top: 0;
  color: #0d47a1;
}

.interaction {
  grid-column: 1 / -1;
  text-align: center;
  margin-top: 2rem;
}

.interaction p {
  font-size: 1.2rem;
  margin-bottom: 1rem;
}

/* Botones */
button.map-button,
button.read-more,
button.create-button {
  background: linear-gradient(90deg, #c94b32, #d38c5d);
  color: white;
  border: none;
  border-radius: 30px;
  padding: 0.8rem 1.8rem;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

button.read-more {
  background: linear-gradient(90deg, #4b32c9, #5d8cd3);
}

button.create-button {
  background: linear-gradient(90deg, #4b32c9, #5d8cd3);
  font-size: 1.1rem;
}

button:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

/* Modal */
.modal {
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.5);
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.modal-content h3 {
  margin-top: 0;
}

.modal-content input,
.modal-content textarea {
  width: 100%;
  margin: 0.5rem 0;
  padding: 0.8rem;
  border: 1px solid #ccc;
  border-radius: 10px;
  resize: vertical;
}

.modal-content button {
  margin-top: 1rem;
  background: linear-gradient(90deg, #4b32c9, #5d8cd3);
  color: white;
  border: none;
  padding: 0.7rem 1.5rem;
  border-radius: 20px;
  cursor: pointer;
  float: right;
}

.close {
  float: right;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.close:hover {
  color: black;
}

@media (max-width: 768px) {
  main {
    grid-template-columns: 1fr;
  }

  .cards {
    grid-template-columns: 1fr;
  }
}

body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f4f4f4;
  display: flex;
  flex-direction: column;
  align-items: center;
}


:root {
  --azul-profundo: #1d2e42;
  --fondo-textura: #f2efe9;
  --texto-gris: #555555;
  --sombra: rgba(0, 0, 0, 0.05);
}


header {
  background: var(--fondo-textura);
  padding: 20px 32px;
  display: flex;
  width: 97%;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #ddd;
  box-shadow: 0 2px 10px var(--sombra);
}

html{
  overflow-x: hidden;
}

.logo {
  display: flex;
  align-items: center;
}

.logo img {
  height: 42px;
  margin-right: 12px;
}

.logo span {
  font-size: 1.9rem;
  font-weight: 800;
  color: var(--azul-profundo);
}

nav a {
  color: var(--azul-profundo);
  margin-left: 20px;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.3s ease;
}

nav a:hover {
  color: #000;
}

.container {
  max-width: 1200px;
  margin: auto;
  padding: 60px 20px 40px;
  text-align: center;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 900;
  margin-bottom: 10px;
  color: var(--azul-profundo);
  background: linear-gradient(90deg, #1d2e42, #3c506d);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.08);
}

.subtitle {
  font-size: 1.3rem;
  color: var(--texto-gris);
  margin-bottom: 60px;
}


.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 30px;
  margin-bottom: 60px;
}

.btn_section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 30px;
  margin-bottom: 60px;
  max-width:100px;
  aling-items:center;
}

.card {
  background: white;
  border-radius: 18px;
  padding: 30px 20px;
  border: 1px solid #ddd;
  box-shadow: 0 6px 14px rgba(0,0,0,0.05);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

.card-icon {
  font-size: 2.2rem;
  margin-bottom: 12px;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--azul-profundo);
  margin-bottom: 10px;
}

.card-text {
  font-size: 0.95rem;
  color: var(--texto-gris);
}

.button {
  background: linear-gradient(90deg, #1d2e42, #3c506d);
  color: white;
  padding: 14px 36px;
  border: none;
  border-radius: 30px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 14px rgba(0,0,0,0.1);
}

.button:hover {
  transform: scale(1.05);
  background: #162635;
}

footer {
  margin-top: 60px;
  padding: 20px;
  width: 97%;
  background: #eceae4;
  text-align: center;
  font-size: 0.9rem;
  color: #555;
}

#map {
display: flex;
justify-content: center;
max-height: 800px;
max-width: 950px;
min-height: 400px;
min-width:350px;
margin: 1rem auto;
border-radius: 10px;
box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
}

.btn {
  margin-top: 2.5rem;
  background: linear-gradient(90deg, var(--acento-1), var(--acento-2));
  background-size: 150% 150%;
  color: white;
  border: none;
  padding: 0.9rem 2.8rem;
  border-radius: 50px;
  font-size: 1.1rem;
  cursor: pointer;
  font-weight: 600;
}



.btn:hover {
  transition: 1s ease-in-out;
  transform: scale(1.05);
  animation: gradienteSuave 1.5s infinite alternate; /* Se aplica la animación */
}

</style>
</head>
<body>

<header>
<div class="user-info">
<img src="perfil.jpg" alt="Foto de perfil" />
<span><strong><?php echo $_SESSION["username"]; ?></strong></span>
</div>
<nav class="menu">
<a href="#">Ajustes</a>
<a href="#">Preferencias</a>
<a href="#">Salir</a>
</nav>
</header>

<main>
<!-- Sección del mapa -->
<div class="map-section">
<div id="map"></div>
<div class="btn_section"><button class="btn" onclick="location.href='Codigo/mapa.html'">Explorar Mapa</button></div>

<div class="cards">
<div class="card">
<div class="card-icon">📌</div>
<div class="card-title">Exploración Regional</div>
<div class="card-text">Recorré cada provincia y conocé sus leyendas más populares.</div>
</div>

<div class="card">
<div class="card-icon">📖</div>
<div class="card-title">Educación Cultural</div>
<div class="card-text">Ideal para enseñar identidad, historia oral y cultura local en escuelas.</div>
</div>

<div class="card">
<div class="card-icon">👻</div>
<div class="card-title">Criaturas y Mitos</div>
<div class="card-text">Historias del Pombero, la Llorona, el Lobizón y muchos más.</div>
</div>

<div class="card">
<div class="card-icon">🔍</div>
<div class="card-title">Investigación</div>
<div class="card-text">Compará versiones de leyendas según cada región o cultura originaria.</div>
</div>
</div>
</div>

<!-- Sección del mito aleatorio -->
<div class="myth-section">
<div class="myth-header">
<img src="autor.jpg" alt="Creador del mito">
<span>Nombre de usuario del creador del mito</span>
</div>
<div class="myth-content">
<p style="padding: 1rem;">Resumen de mito aleatorio elegido de los mitos registrados</p>
</div>
<div style="text-align: center;">
<button class="read-more">Leer más</button>
</div>
<!-- Invitación a crear -->
<div class="interaction">
<p>¡Cuéntanos alguna historia popular de tu provincia!</p>
<button class="create-button" onclick="document.getElementById('modal').style.display='flex'">Crear</button>
</div>
</main>
</div>



<!-- Modal -->
<div class="modal" id="modal">
<div class="modal-content">
<span class="close" onclick="document.getElementById('modal').style.display='none'">&times;</span>
<h3>Subí tu leyenda</h3>
<input type="text" placeholder="Tu nombre o seudónimo">
<input type="text" placeholder="Provincia">
<textarea rows="5" placeholder="Escribí acá tu mito o leyenda..."></textarea>
<button onclick="document.getElementById('modal').style.display='none'">Enviar</button>
</div>
</div>

</body>
</html>
