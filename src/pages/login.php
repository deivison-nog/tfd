<?php
if (is_logged_in()) {
    redirect(user_home_url());
}

if (is_post()) {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_login($username, $password)) {
        flash('success', 'Login realizado com sucesso.');
        redirect(user_home_url());
    }

    flash('error', 'Usuário ou senha inválidos.');
    redirect('/index.php?page=login');
}
?>
<section class="auth-card">
    <h2>Acesso ao Sistema TFD</h2>
    <p>Use as credenciais de demonstração para testar.</p>
    <form method="post" class="grid-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Usuário
            <input type="text" name="username" required placeholder="tfdcolares">
        </label>
        <label>Senha
            <input type="password" name="password" required placeholder="tfd123">
        </label>
        <button type="submit">Entrar</button>
    </form>
    <div class="demo-box">
        <strong>Demo:</strong><br>
        <code>tfdcolares</code> (Administrativo) | senha <code>tfd123</code><br>
        <code>assistente.social</code> (Assistente Social) | senha <code>tfd123</code><br>
        <code>medico.tfd</code> (Médico) | senha <code>tfd123</code><br>
        <code>auxiliar.adm</code> (Auxiliar Administrativo) | senha <code>tfd123</code>
    </div>
</section>
