<section>
    <div class="section-head"><h2>Fluxo operacional do TFD</h2></div>
    <div class="panel">
        <p>Fluxo completo do processo TFD com status operacionais e administrativos:</p>
        <svg viewBox="0 0 1280 420" class="flow-svg" role="img" aria-label="Fluxo operacional TFD">
            <defs>
                <marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
                    <path d="M 0 0 L 10 5 L 0 10 z" fill="#2f4f8f" />
                </marker>
            </defs>

            <rect x="20" y="30" width="140" height="60" rx="10" /><text x="90" y="65">Login</text>
            <rect x="190" y="30" width="160" height="60" rx="10" /><text x="270" y="65">Dashboard</text>
            <rect x="380" y="30" width="190" height="60" rx="10" /><text x="475" y="65">Busca / Cadastro Paciente</text>
            <rect x="600" y="30" width="190" height="60" rx="10" /><text x="695" y="65">Abertura Processo TFD</text>
            <rect x="820" y="30" width="190" height="60" rx="10" /><text x="915" y="65">Campos Obrigatórios</text>
            <rect x="1040" y="30" width="200" height="60" rx="10" /><text x="1140" y="65">Acompanhante + Docs</text>

            <rect x="140" y="180" width="180" height="60" rx="10" /><text x="230" y="215">Análise</text>
            <rect x="350" y="180" width="230" height="60" rx="10" /><text x="465" y="215">Autorizado / Pendente / Negado</text>
            <rect x="610" y="180" width="180" height="60" rx="10" /><text x="700" y="215">Agendamento</text>
            <rect x="820" y="180" width="180" height="60" rx="10" /><text x="910" y="215">Em viagem / Retorno</text>
            <rect x="1030" y="180" width="180" height="60" rx="10" /><text x="1120" y="215">Conclusão</text>

            <path d="M160 60 L190 60" marker-end="url(#arrow)" />
            <path d="M350 60 L380 60" marker-end="url(#arrow)" />
            <path d="M570 60 L600 60" marker-end="url(#arrow)" />
            <path d="M790 60 L820 60" marker-end="url(#arrow)" />
            <path d="M1010 60 L1040 60" marker-end="url(#arrow)" />
            <path d="M1140 90 L1140 150 L230 150 L230 180" marker-end="url(#arrow)" />
            <path d="M320 210 L350 210" marker-end="url(#arrow)" />
            <path d="M580 210 L610 210" marker-end="url(#arrow)" />
            <path d="M790 210 L820 210" marker-end="url(#arrow)" />
            <path d="M1000 210 L1030 210" marker-end="url(#arrow)" />

            <text x="20" y="305" class="legend">Status suportados: cadastrado, aguardando análise, pendente de documentos, autorizado, agendado, em viagem, retornado, concluído, negado, cancelado.</text>
        </svg>
    </div>
</section>
