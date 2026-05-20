<?php
$isLogged = is_logged_in();
$enterUrl = $isLogged ? app_url('index.php?page=dashboard') : app_url('index.php?page=login');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema TFD – Transporte Fora do Domicílio</title>
    <link rel="stylesheet" href="<?= e(asset_url('style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('home.css')) ?>">
</head>
<body class="home-body">

<!-- NAVBAR -->
<header class="home-nav">
    <div class="home-nav-inner">
        <span class="home-nav-logo">🏥 Sistema TFD</span>
        <nav class="home-nav-links">
            <a href="#funcionalidades">Funcionalidades</a>
            <a href="#fluxo">Fluxo</a>
            <a href="#como-funciona">Como usar</a>
            <a href="<?= e($enterUrl) ?>" class="nav-btn"><?= $isLogged ? 'Ir ao painel' : 'Entrar' ?></a>
        </nav>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">Gestão de Saúde Pública</div>
        <h1 class="hero-title">Transporte Fora do<br>Domicílio <span class="accent">simplificado</span></h1>
        <p class="hero-subtitle">
            Gerencie solicitações, pacientes, acompanhantes, documentos e viagens de TFD
            em um sistema web organizado, rápido e fácil de usar.
        </p>
        <div class="hero-actions">
            <a href="<?= e($enterUrl) ?>" class="cta-btn">
                <?= $isLogged ? 'Acessar o painel' : 'Acessar o sistema' ?>
            </a>
            <a href="#funcionalidades" class="cta-outline">Conhecer o sistema</a>
        </div>
        <div class="hero-demo">
            <strong>Demonstração:</strong>&nbsp; usuário <code>tfdcolares</code> &nbsp;|&nbsp; senha <code>tfd123</code>
        </div>
    </div>

    <div class="hero-visual" aria-hidden="true">
        <svg viewBox="0 0 480 320" xmlns="http://www.w3.org/2000/svg">
            <!-- Background card -->
            <rect x="20" y="20" width="440" height="280" rx="18" fill="#fff" opacity="0.85"/>
            <!-- Sidebar mockup -->
            <rect x="20" y="20" width="110" height="280" rx="18" fill="#20345f"/>
            <rect x="38" y="50" width="74" height="10" rx="5" fill="#c6d3f0"/>
            <rect x="38" y="70" width="74" height="8" rx="4" fill="#4a6fa8" opacity=".7"/>
            <rect x="38" y="92" width="60" height="8" rx="4" fill="#4a6fa8" opacity=".5"/>
            <rect x="38" y="114" width="66" height="8" rx="4" fill="#4a6fa8" opacity=".5"/>
            <rect x="38" y="136" width="58" height="8" rx="4" fill="#4a6fa8" opacity=".4"/>
            <rect x="38" y="158" width="72" height="8" rx="4" fill="#4a6fa8" opacity=".4"/>
            <rect x="38" y="180" width="50" height="8" rx="4" fill="#4a6fa8" opacity=".3"/>
            <!-- Main area mockup -->
            <rect x="150" y="38" width="120" height="14" rx="5" fill="#e3ecfa"/>
            <!-- Stat cards -->
            <rect x="150" y="68" width="72" height="50" rx="8" fill="#eaf2ff"/>
            <rect x="232" y="68" width="72" height="50" rx="8" fill="#edf9f2"/>
            <rect x="314" y="68" width="72" height="50" rx="8" fill="#fff5e8"/>
            <rect x="396" y="68" width="44" height="50" rx="8" fill="#f0eaff"/>
            <!-- Numbers in cards -->
            <text x="186" y="95" font-family="Arial" font-size="16" font-weight="bold" fill="#2f4f8f" text-anchor="middle">12</text>
            <text x="268" y="95" font-family="Arial" font-size="16" font-weight="bold" fill="#1c7c54" text-anchor="middle">5</text>
            <text x="350" y="95" font-family="Arial" font-size="16" font-weight="bold" fill="#b25c00" text-anchor="middle">8</text>
            <text x="418" y="95" font-family="Arial" font-size="16" font-weight="bold" fill="#6b3ea6" text-anchor="middle">3</text>
            <!-- Table rows -->
            <rect x="150" y="136" width="290" height="22" rx="5" fill="#f4f8ff"/>
            <rect x="150" y="164" width="290" height="22" rx="5" fill="#fff"/>
            <rect x="150" y="192" width="290" height="22" rx="5" fill="#f4f8ff"/>
            <rect x="150" y="220" width="290" height="22" rx="5" fill="#fff"/>
            <!-- Badge in table row -->
            <rect x="370" y="140" width="58" height="14" rx="7" fill="#e5ecff"/>
            <text x="399" y="150" font-family="Arial" font-size="8" fill="#244277" text-anchor="middle">autorizado</text>
            <rect x="370" y="168" width="62" height="14" rx="7" fill="#dcf7e8"/>
            <text x="401" y="178" font-family="Arial" font-size="8" fill="#175c3f" text-anchor="middle">em viagem</text>
            <rect x="370" y="196" width="68" height="14" rx="7" fill="#fff5e8"/>
            <text x="404" y="206" font-family="Arial" font-size="8" fill="#b25c00" text-anchor="middle">aguardando</text>
            <rect x="370" y="224" width="58" height="14" rx="7" fill="#ffe6e3"/>
            <text x="399" y="234" font-family="Arial" font-size="8" fill="#8d1f18" text-anchor="middle">pendente</text>
        </svg>
    </div>
</section>

<!-- FUNCIONALIDADES -->
<section id="funcionalidades" class="features">
    <div class="section-container">
        <h2>Tudo que você precisa para gerir o TFD</h2>
        <p class="section-sub">Módulos integrados que cobrem todo o fluxo operacional</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">👤</div>
                <h3>Pacientes</h3>
                <p>Cadastro completo com CPF, CNS/cartão SUS, endereço e histórico de processos vinculados.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Processos TFD</h3>
                <p>Controle de solicitações com número, ano, local de tratamento, CID, especialidade, prioridade e status.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Acompanhantes</h3>
                <p>Vínculo de acompanhante ao processo com dados de parentesco, CPF e contato.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📄</div>
                <h3>Documentos</h3>
                <p>Registro e controle de laudos, CPF, cartão SUS, comprovantes e dados bancários por entidade.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚐</div>
                <h3>Viagens</h3>
                <p>Agendamento de transporte com data, horário, motorista, veículo e status de execução/retorno.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Dashboard</h3>
                <p>Visão rápida dos indicadores: totais por módulo e distribuição de processos por status.</p>
            </div>
        </div>
    </div>
</section>

<!-- FLUXO OPERACIONAL -->
<section id="fluxo" class="flow-section">
    <div class="section-container">
        <h2>Fluxo operacional do TFD</h2>
        <p class="section-sub">Da abertura do processo até a conclusão da viagem</p>
        <div class="flow-steps">
            <?php
            $steps = [
                ['🔑', 'Login', 'Autenticação segura no sistema'],
                ['📊', 'Dashboard', 'Visão geral e indicadores'],
                ['👤', 'Paciente', 'Busca ou cadastro do paciente'],
                ['📋', 'Processo TFD', 'Abertura com campos obrigatórios'],
                ['👥', 'Acompanhante', 'Vínculo e documentos'],
                ['🔍', 'Análise', 'Autorizado, pendente ou negado'],
                ['🚐', 'Agendamento', 'Definição da viagem'],
                ['✅', 'Conclusão', 'Retorno e encerramento'],
            ];
            foreach ($steps as $i => $step):
            ?>
                <div class="flow-step">
                    <div class="flow-step-icon"><?= $step[0] ?></div>
                    <div class="flow-step-label"><?= e($step[1]) ?></div>
                    <div class="flow-step-desc"><?= e($step[2]) ?></div>
                    <?php if ($i < count($steps) - 1): ?>
                        <div class="flow-step-arrow" aria-hidden="true">→</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="status-list">
            <strong>Status operacionais:</strong>
            <?php
            $statuses = ['cadastrado', 'aguardando análise', 'pendente de documentos', 'autorizado', 'agendado', 'em viagem', 'retornado', 'concluído', 'negado', 'cancelado'];
            foreach ($statuses as $s):
            ?>
                <span class="status-pill"><?= e($s) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- COMO USAR -->
<section id="como-funciona" class="how-section">
    <div class="section-container">
        <h2>Como executar localmente</h2>
        <p class="section-sub">Setup rápido em 3 passos</p>
        <div class="steps-grid">
            <div class="step">
                <span class="step-num">1</span>
                <h4>Clone e acesse a pasta</h4>
                <pre><code>git clone https://github.com/deivison-nog/tfd
cd tfd</code></pre>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <h4>Inicie o servidor PHP</h4>
                <pre><code>php -S 127.0.0.1:8000 -t public</code></pre>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <h4>Acesse no navegador</h4>
                <pre><code>http://127.0.0.1:8000</code></pre>
            </div>
        </div>
        <div class="cred-box">
            <strong>Credenciais de demonstração:</strong>&nbsp;
            usuário <code>tfdcolares</code> &nbsp;/&nbsp; senha <code>tfd123</code>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="cta-section">
    <div class="section-container">
        <h2>Pronto para começar?</h2>
        <p>Acesse o sistema com as credenciais demo e explore todos os módulos.</p>
        <a href="<?= e($enterUrl) ?>" class="cta-btn"><?= $isLogged ? 'Ir ao painel' : 'Acessar agora' ?></a>
    </div>
</section>

<!-- FOOTER SITE -->
<footer class="home-footer">
    <div class="home-footer-inner">
        <span>Sistema TFD – Transporte Fora do Domicílio</span>
        <span>PHP + SQLite · Código aberto</span>
    </div>
</footer>

<script src="<?= e(asset_url('app.js')) ?>"></script>
</body>
</html>
