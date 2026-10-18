<?php

/*
|--------------------------------------------------------------------------
| Catálogo de cenários SINTÉTICOS de suporte de TI
|--------------------------------------------------------------------------
| Elaborado manualmente para testar o protótipo. Não deriva de chamados reais
| e não deve ser apresentado como evidência do funcionamento real do suporte.
|
| weight  = frequência relativa do cenário (gera recorrência)
| article = se já existe artigo na base de conhecimento para o problema
*/

return [
    'categories' => [
        ['name' => 'Acesso e senhas', 'slug' => 'acesso-e-senhas', 'color' => '#6366f1', 'sla_response_minutes' => 15, 'sla_resolution_minutes' => 120],
        ['name' => 'Rede e internet', 'slug' => 'rede-e-internet', 'color' => '#0ea5e9', 'sla_response_minutes' => 15, 'sla_resolution_minutes' => 240],
        ['name' => 'Impressoras', 'slug' => 'impressoras', 'color' => '#f59e0b', 'sla_response_minutes' => 30, 'sla_resolution_minutes' => 480],
        ['name' => 'E-mail', 'slug' => 'e-mail', 'color' => '#ec4899', 'sla_response_minutes' => 20, 'sla_resolution_minutes' => 240],
        ['name' => 'Hardware', 'slug' => 'hardware', 'color' => '#14b8a6', 'sla_response_minutes' => 30, 'sla_resolution_minutes' => 1440],
        ['name' => 'Sistemas internos', 'slug' => 'sistemas-internos', 'color' => '#8b5cf6', 'sla_response_minutes' => 20, 'sla_resolution_minutes' => 480],
        ['name' => 'Software e instalação', 'slug' => 'software-e-instalacao', 'color' => '#22c55e', 'sla_response_minutes' => 60, 'sla_resolution_minutes' => 1440],
        ['name' => 'Telefonia', 'slug' => 'telefonia', 'color' => '#ef4444', 'sla_response_minutes' => 30, 'sla_resolution_minutes' => 480],
    ],

    'departments' => ['Financeiro', 'Comercial', 'RH', 'Jurídico', 'Logística', 'Atendimento', 'Diretoria', 'Compras', 'Marketing', 'Produção'],

    'scenarios' => [
        [
            'category' => 'acesso-e-senhas', 'weight' => 14, 'priority' => 'alta', 'article' => true,
            'titles' => ['Senha do Windows expirada', 'Não consigo entrar no computador, senha expirou', 'Bloqueio de usuário na rede'],
            'descriptions' => [
                'Ao ligar o computador aparece a mensagem de que a senha expirou e não deixa trocar.',
                'Meu usuário foi bloqueado depois de errar a senha algumas vezes. Preciso acessar com urgência.',
                'Aparece "A conta referenciada está bloqueada" ao tentar fazer login.',
            ],
            'article_title' => 'Desbloqueio de conta e redefinição de senha no Active Directory',
            'solution' => "1. Abrir 'Usuários e Computadores do AD' e localizar a conta.\n2. Desmarcar 'Conta bloqueada' e redefinir a senha temporária.\n3. Marcar 'O usuário deve alterar a senha no próximo logon'.\n4. Orientar o usuário sobre a política de senha (12 caracteres, sem repetir as últimas 5).",
            'keywords' => 'senha, bloqueio, conta bloqueada, active directory, ad, login, expirada, logon',
        ],
        [
            'category' => 'acesso-e-senhas', 'weight' => 7, 'priority' => 'media', 'article' => true,
            'titles' => ['Liberar acesso à pasta da rede', 'Sem permissão na pasta compartilhada', 'Acesso negado ao drive do setor'],
            'descriptions' => [
                'Preciso de acesso à pasta compartilhada do setor, aparece acesso negado.',
                'Fui transferido de setor e não consigo abrir a unidade de rede do novo departamento.',
                'O mapeamento da unidade aparece com X vermelho e diz que não tenho permissão.',
            ],
            'article_title' => 'Concessão de permissão em pastas compartilhadas (grupos de segurança)',
            'solution' => "Permissões são dadas por grupo, nunca por usuário.\n1. Confirmar com o gestor da área a autorização.\n2. Adicionar o usuário ao grupo GS_<SETOR>_Leitura ou GS_<SETOR>_Escrita.\n3. Pedir para o usuário fazer logoff/logon (ou executar 'gpupdate /force') para renovar o token.",
            'keywords' => 'pasta, compartilhamento, permissão, acesso negado, unidade de rede, grupo, mapeamento',
        ],
        [
            'category' => 'acesso-e-senhas', 'weight' => 4, 'priority' => 'media', 'article' => false,
            'titles' => ['Autenticador de dois fatores não funciona', 'Troquei de celular e perdi o 2FA'],
            'descriptions' => [
                'Troquei de celular e agora o código de verificação não chega mais.',
                'O aplicativo autenticador mostra código mas o sistema diz que é inválido.',
            ],
            'solution' => 'Redefinido o método MFA do usuário no portal de administração e orientado novo cadastro do aplicativo autenticador. Conferido que o relógio do celular estava em horário automático.',
            'keywords' => '2fa, mfa, autenticador, código, verificação, celular',
        ],
        [
            'category' => 'rede-e-internet', 'weight' => 12, 'priority' => 'alta', 'article' => true,
            'titles' => ['VPN não conecta', 'Erro ao conectar na VPN em home office', 'FortiClient não conecta'],
            'descriptions' => [
                'Estou em home office e a VPN fica em "conectando" e depois dá erro.',
                'O FortiClient mostra "Credential or SSLVPN configuration is wrong (-7200)".',
                'Consegui conectar ontem, hoje a VPN cai a cada 2 minutos.',
            ],
            'article_title' => 'VPN (FortiClient) não conecta ou desconecta sozinha',
            'solution' => "1. Verificar se a senha de rede não expirou (a VPN usa a mesma credencial).\n2. Conferir o endereço do gateway e a porta 10443 no perfil.\n3. Desativar a economia de energia da placa de rede.\n4. Se persistir, reinstalar o FortiClient na versão homologada (7.2).",
            'keywords' => 'vpn, forticlient, home office, acesso remoto, conexão, -7200, gateway',
        ],
        [
            'category' => 'rede-e-internet', 'weight' => 9, 'priority' => 'alta', 'article' => true,
            'titles' => ['Sem internet na estação', 'Computador sem rede', 'Internet caiu no setor'],
            'descriptions' => [
                'O computador mostra "Sem internet" com um globo no ícone de rede.',
                'Todos do setor estão sem internet desde cedo.',
                'O cabo está ligado mas o Windows diz rede não identificada.',
            ],
            'article_title' => 'Estação sem rede: checklist de diagnóstico',
            'solution' => "1. Verificar o LED da porta e o cabo até o ponto de rede.\n2. 'ipconfig /all': se o IP começa com 169.254, o DHCP não respondeu.\n3. 'ipconfig /release' e 'ipconfig /renew'.\n4. Se for o setor inteiro, verificar o switch do rack e a porta de uplink.",
            'keywords' => 'internet, rede, cabo, dhcp, ip, switch, sem conexão, 169.254',
        ],
        [
            'category' => 'rede-e-internet', 'weight' => 5, 'priority' => 'media', 'article' => false,
            'titles' => ['Wi-Fi lento na sala de reuniões', 'Sinal fraco do Wi-Fi'],
            'descriptions' => [
                'Na sala de reuniões a internet sem fio fica muito lenta durante as chamadas.',
                'O notebook desconecta do Wi-Fi quando vou para o fundo do corredor.',
            ],
            'solution' => 'Identificado access point sobrecarregado no canal 6. Alterado para canal 11 e habilitada banda de 5 GHz. Solicitada a instalação de mais um AP para o corredor.',
            'keywords' => 'wifi, wi-fi, sem fio, lento, sinal, access point, ap',
        ],
        [
            'category' => 'impressoras', 'weight' => 13, 'priority' => 'media', 'article' => true,
            'titles' => ['Impressora não imprime', 'Documento fica na fila de impressão', 'Impressora do setor parou'],
            'descriptions' => [
                'Mando imprimir e o documento fica parado na fila, não sai nada.',
                'A impressora aparece como offline no computador.',
                'Ninguém do setor consegue imprimir desde a manhã.',
            ],
            'article_title' => 'Fila de impressão travada / impressora offline',
            'solution' => "1. No servidor de impressão, parar o serviço 'Spooler'.\n2. Apagar o conteúdo de C:\\Windows\\System32\\spool\\PRINTERS.\n3. Iniciar o serviço novamente.\n4. Se a impressora aparece offline, conferir se o IP fixo dela (etiqueta) responde ao ping.",
            'keywords' => 'impressora, imprimir, fila, spooler, offline, impressão',
        ],
        [
            'category' => 'impressoras', 'weight' => 6, 'priority' => 'baixa', 'article' => true,
            'titles' => ['Papel atolado na impressora', 'Impressora com mancha na impressão', 'Trocar toner'],
            'descriptions' => [
                'A impressora diz que tem papel atolado mas não tem nada visível.',
                'As folhas saem com uma faixa preta do lado.',
                'Está aparecendo aviso de toner baixo.',
            ],
            'article_title' => 'Atolamento de papel e qualidade de impressão',
            'solution' => 'Abrir a tampa traseira e a gaveta 2: o sensor costuma acusar papel preso no rolo de duplex. Para manchas, limpar o cilindro com pano seco. Toner: há estoque no almoxarifado da TI (modelo TN-3472).',
            'keywords' => 'papel atolado, toner, mancha, impressão, cilindro, duplex',
        ],
        [
            'category' => 'e-mail', 'weight' => 10, 'priority' => 'media', 'article' => true,
            'titles' => ['Outlook não abre', 'Outlook travando ao iniciar', 'Outlook pede senha toda hora'],
            'descriptions' => [
                'O Outlook fica em "Carregando perfil" e não abre.',
                'Toda vez que abro o Outlook ele pede a senha de novo.',
                'O Outlook travou e agora só abre em modo de segurança.',
            ],
            'article_title' => 'Outlook não inicia ou pede senha repetidamente',
            'solution' => "1. Fechar o Outlook e abrir 'outlook.exe /safe' para testar suplementos.\n2. Desativar suplementos recentes.\n3. Se pedir senha: limpar credenciais do Office no Gerenciador de Credenciais do Windows.\n4. Último caso: criar novo perfil em Painel de Controle > Email.",
            'keywords' => 'outlook, email, e-mail, perfil, senha, travando, suplemento, office',
        ],
        [
            'category' => 'e-mail', 'weight' => 6, 'priority' => 'media', 'article' => true,
            'titles' => ['Caixa de e-mail cheia', 'Não consigo enviar e-mails, caixa cheia'],
            'descriptions' => [
                'Recebi aviso de que minha caixa de correio está quase cheia.',
                'Os e-mails voltam dizendo que a caixa está cheia.',
            ],
            'article_title' => 'Caixa de correio cheia: arquivamento online',
            'solution' => 'Orientar a ativar o Arquivo Morto Online e aplicar a política de retenção de 2 anos. Ensinar a localizar os maiores itens (Arquivo > Ferramentas > Limpeza de Caixa de Correio).',
            'keywords' => 'caixa cheia, cota, armazenamento, arquivo morto, email, e-mail',
        ],
        [
            'category' => 'e-mail', 'weight' => 3, 'priority' => 'alta', 'article' => false,
            'titles' => ['Recebi e-mail suspeito', 'Possível phishing'],
            'descriptions' => [
                'Recebi um e-mail pedindo para atualizar meus dados bancários, parece golpe.',
                'Cliquei em um link de um e-mail estranho, o que faço?',
            ],
            'solution' => 'Mensagem confirmada como phishing. Remetente bloqueado no filtro, mensagem removida das caixas via busca de conteúdo. Senha do usuário redefinida por precaução e sessões encerradas.',
            'keywords' => 'phishing, golpe, e-mail suspeito, segurança, link, malicioso',
        ],
        [
            'category' => 'hardware', 'weight' => 7, 'priority' => 'media', 'article' => true,
            'titles' => ['Computador muito lento', 'PC demorando para ligar', 'Notebook travando'],
            'descriptions' => [
                'O computador demora quase 10 minutos para ligar e trava abrindo planilhas.',
                'O notebook esquenta e fica lento depois de algumas horas.',
                'Tudo trava quando abro mais de dois programas.',
            ],
            'article_title' => 'Lentidão em estações de trabalho',
            'solution' => "1. Gerenciador de Tarefas: verificar uso de disco/memória.\n2. Desativar programas na inicialização.\n3. Verificar saúde do disco (CrystalDiskInfo). HD mecânico com setores ruins = troca por SSD.\n4. Máquinas com 4 GB de RAM entram na fila de upgrade.",
            'keywords' => 'lento, lentidão, travando, memória, disco, ssd, inicialização, desempenho',
        ],
        [
            'category' => 'hardware', 'weight' => 5, 'priority' => 'baixa', 'article' => false,
            'titles' => ['Monitor não liga', 'Segundo monitor não é reconhecido', 'Teclado com teclas falhando'],
            'descriptions' => [
                'O segundo monitor fica com tela preta escrito "sem sinal".',
                'O monitor pisca e apaga.',
                'Algumas teclas do teclado não funcionam.',
            ],
            'solution' => 'Substituído o cabo HDMI e ajustada a projeção para "Estender" (Win+P). Periférico com defeito trocado pelo estoque.',
            'keywords' => 'monitor, tela, hdmi, sem sinal, teclado, mouse, periférico',
        ],
        [
            'category' => 'sistemas-internos', 'weight' => 11, 'priority' => 'alta', 'article' => true,
            'titles' => ['Erro ao emitir nota fiscal no ERP', 'ERP não gera NF-e', 'Rejeição na nota fiscal'],
            'descriptions' => [
                'Ao transmitir a nota aparece "Rejeição 539: duplicidade de NF-e".',
                'O ERP dá erro de certificado ao emitir nota fiscal.',
                'A nota fica com status "aguardando retorno" há horas.',
            ],
            'article_title' => 'Erros comuns na emissão de NF-e pelo ERP',
            'solution' => "- Rejeição 539: consultar a chave na SEFAZ; se autorizada, apenas sincronizar o status no ERP.\n- Erro de certificado: verificar validade do certificado A1 no servidor e reiniciar o serviço de integração fiscal.\n- 'Aguardando retorno': checar fila do serviço de mensageria e contingência da SEFAZ.",
            'keywords' => 'erp, nota fiscal, nf-e, nfe, rejeição, sefaz, certificado, 539, fiscal',
        ],
        [
            'category' => 'sistemas-internos', 'weight' => 8, 'priority' => 'media', 'article' => true,
            'titles' => ['Relatório do ERP não carrega', 'Lentidão no sistema de gestão', 'ERP fechando sozinho'],
            'descriptions' => [
                'O relatório de vendas fica carregando e não termina.',
                'O sistema fecha sozinho quando tento exportar para Excel.',
                'O ERP está muito lento para todos.',
            ],
            'article_title' => 'Lentidão e travamentos no ERP',
            'solution' => "1. Verificar no monitor do banco se há bloqueios (locks) longos e encerrar a sessão travada.\n2. Para relatórios grandes, orientar filtro por período menor.\n3. Exportação para Excel: atualizar o componente do cliente ERP para a versão 2026.3.",
            'keywords' => 'erp, relatório, lento, travando, exportar, excel, banco, lock',
        ],
        [
            'category' => 'sistemas-internos', 'weight' => 4, 'priority' => 'media', 'article' => false,
            'titles' => ['Criar usuário no sistema de ponto', 'Novo colaborador sem acesso ao ponto eletrônico'],
            'descriptions' => [
                'Colaborador novo do setor ainda não tem acesso ao sistema de ponto.',
                'Preciso liberar o perfil de gestor no ponto eletrônico.',
            ],
            'solution' => 'Usuário criado no sistema de ponto com o perfil solicitado pelo RH e vinculado à escala do setor.',
            'keywords' => 'ponto, usuário, acesso, colaborador, rh, perfil',
        ],
        [
            'category' => 'software-e-instalacao', 'weight' => 6, 'priority' => 'baixa', 'article' => true,
            'titles' => ['Instalar programa', 'Solicitação de instalação de software', 'Preciso do leitor de PDF'],
            'descriptions' => [
                'Preciso instalar um programa para abrir arquivos de projeto.',
                'O computador pede senha de administrador para instalar.',
                'Não consigo abrir arquivos PDF.',
            ],
            'article_title' => 'Instalação de softwares homologados',
            'solution' => 'Softwares homologados são instalados pelo Portal da Empresa (Centro de Software). Fora da lista: solicitar aprovação do gestor e da TI. Nunca fornecer senha de administrador local.',
            'keywords' => 'instalar, instalação, software, programa, administrador, pdf, homologado',
        ],
        [
            'category' => 'software-e-instalacao', 'weight' => 5, 'priority' => 'media', 'article' => false,
            'titles' => ['Excel travando com planilha grande', 'Office pedindo ativação'],
            'descriptions' => [
                'O Excel trava quando abro a planilha de fechamento.',
                'O Word mostra "Produto não licenciado".',
            ],
            'solution' => 'Licença do Office reatribuída no portal e feito logoff/logon na conta. Planilha com fórmulas voláteis convertida e cálculo alterado para manual durante a edição.',
            'keywords' => 'excel, office, word, ativação, licença, planilha, travando',
        ],
        [
            'category' => 'telefonia', 'weight' => 5, 'priority' => 'media', 'article' => true,
            'titles' => ['Ramal sem tom', 'Telefone IP não registra', 'Ramal mudo'],
            'descriptions' => [
                'O telefone do ramal não dá linha.',
                'O aparelho mostra "sem registro" no visor.',
                'Ligo e a outra pessoa não me escuta.',
            ],
            'article_title' => 'Telefone IP sem registro ou sem áudio',
            'solution' => "1. Verificar se o aparelho está na porta PoE correta (VLAN de voz).\n2. Reiniciar o aparelho tirando o cabo de rede.\n3. Sem áudio em apenas um sentido costuma ser NAT/SIP ALG: abrir chamado com a operadora se for externo.",
            'keywords' => 'ramal, telefone, voip, sip, registro, poe, sem tom, áudio',
        ],
    ],

    'requester_messages' => [
        'Alguém pode me ajudar? Estou parado.',
        'Bom dia, isso está atrapalhando o fechamento.',
        'Já reiniciei e continua igual.',
        'É urgente, tenho reunião daqui a pouco.',
        'Segue print do erro.',
        'Isso já aconteceu outras vezes.',
    ],

    'technician_replies' => [
        'Olá! Estou verificando o seu chamado.',
        'Pode me informar o número de patrimônio da máquina?',
        'Vou acessar remotamente, pode aceitar a conexão?',
        'Consegue testar agora, por favor?',
        'Estou consultando como resolvemos isso da última vez.',
    ],
];
