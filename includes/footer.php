<!-- includes/footer.php -->
<!-- Aquest fitxer defineix el peu de pàgina global del lloc web. -->
<!-- Està dissenyat per ser inclòs a la part inferior de cada pàgina per proporcionar consistència. -->
<!-- Utilitza classes de Bootstrap 5 per a l'estil i la disposició responsive. -->

<footer class="bg-dark text-white mt-5 py-4"> <!-- `mt-5` afegeix un marge superior per separar-lo del contingut principal. -->
    <div class="container">
        <div class="row">
            <!-- Columna 1: Informació de la marca -->
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="fw-bold">CarSharing</h6>
                <p class="small text-muted">Plataforma de compartició de viatges segura i de confiança.</p>
            </div>
            <!-- Columna 2: Enllaços de navegació ràpida -->
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="fw-bold">Enllaços Ràpids</h6>
                <ul class="list-unstyled small">
                    <!-- Cada enllaç apunta a l'index.php amb un paràmetre 'action' que determina quin controlador s'executarà. -->
                    <li><a href="index.php?action=rutes_disponibles" class="text-muted text-decoration-none">Rutes Disponibles</a></li>
                    <li><a href="index.php?action=afegir_ruta" class="text-muted text-decoration-none">Crear Ruta</a></li>
                    <li><a href="index.php?action=mis_rutes" class="text-muted text-decoration-none">Les Meves Rutes</a></li>
                </ul>
            </div>
            <!-- Columna 3: Informació de contacte -->
            <div class="col-md-4">
                <h6 class="fw-bold">Contacte</h6>
                <p class="small text-muted">
                    Correu: <a href="mailto:info@carsharing.com" class="text-muted text-decoration-none">info@carsharing.com</a><br>
                    Telèfon: +34 XXX XXX XXX
                </p>
            </div>
        </div>
        <hr class="bg-secondary"> <!-- Línia horitzontal per separar les seccions. -->
        <!-- Secció de Copyright -->
        <div class="text-center small text-muted">
            &copy; 2025 CarSharing. Tots els drets reservats.
        </div>
    </div>
</footer>