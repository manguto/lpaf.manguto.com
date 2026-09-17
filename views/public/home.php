<section>
    <h1>PHP Admin Framework</h1>
    <p>Fundação administrativa leve, segura e baseada em arquivos CSV.</p><?php if (!isset($_SESSION['user_id'])): ?><a class="button" href="/login">Entrar</a><?php else: ?><a class="button" href="/app">Abrir aplicação</a><?php endif; ?>
</section>