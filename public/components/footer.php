<!-- Footer with Inline Styles -->
<style>
.footer-custom {
    background: #EEEEEE;
    margin-top: 3rem;
    padding: 2.5rem 0 1.5rem;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

.footer-custom h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
}

.footer-custom .footer-logo {
    color: var(--primary-color, #CA773B);
    font-size: 1.1rem;
    font-weight: 700;
}

.footer-custom .footer-links a {
    color: #6c757d;
    text-decoration: none;
    transition: color 0.3s ease;
    display: block;
    padding: 0.25rem 0;
}

.footer-custom .footer-links a:hover {
    color: var(--primary-color, #CA773B);
    text-decoration: none;
}

.footer-custom .footer-bottom {
    border-top: 1px solid #dee2e6;
    margin-top: 1.5rem;
    padding-top: 1rem;
}

.footer-custom .footer-bottom p {
    margin-bottom: 0;
    color: #6c757d;
    font-size: 0.875rem;
}
</style>

<footer class="footer-custom">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h6 class="footer-logo mb-2">
                    <img src="assets/images/icon.svg" alt="CloutHub" height="20" class="me-2">
                    CloutHub
                </h6>
                <p class="text-muted small mb-0">
                    Plataforma completa de gestão de relacionamento com clientes.
                </p>
            </div>
            <div class="col-md-3">
                <h6>Links Rápidos</h6>
                <ul class="list-unstyled small footer-links">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="crm-leads.php">Leads</a></li>
                    <li><a href="scheduling.php">Agendamento</a></li>
                    <li><a href="reports.php">Relatórios</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h6>Suporte</h6>
                <ul class="list-unstyled small footer-links">
                    <li><a href="#">Documentação</a></li>
                    <li><a href="#">Contato</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p>
                        © <?php echo date('Y'); ?> CloutHub. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>
                        Versão 1.0.0
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>