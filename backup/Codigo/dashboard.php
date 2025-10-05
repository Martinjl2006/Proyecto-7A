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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página de Mitos</title>
  <link rel="stylesheet" href="usuario.css">
</head>
<body>
  <header>
    <div class="perfil">
      <div class="foto">foto de<br>perfil</div>
      <span><strong><?php echo $_SESSION["username"]; ?></strong></span>
    </div>
    <button class="explorar">Explorar mapa</button>
  </header>

  <main>
    <!-- Sección Mitos Leídos -->
    <section class="seccion-mitos">
      <h2>Mitos Leídos</h2>
      <div class="grid">
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
      </div>
    </section>

    <!-- Sección Mitos Favoritos -->
    <section class="seccion-mitos">
      <h2>Mitos Favoritos</h2>
      <div class="grid">
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
      </div>
    </section>

    <!-- Sección Mitos Subidos/Aportados -->
    <section class="seccion-mitos">
      <h2>Mitos Subidos/Aportados</h2>
      <div class="grid">
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
        <div class="mito">
          <div class="imagen-mito">imagen<br>de mito</div>
          <h3>Título del mito</h3>
          <p>Texto del mito</p>
          <button class="leer-mas">Leer más</button>
        </div>
      </div>
    </section>
  </main>

  <footer>Footer</footer>
</body>
</html>
