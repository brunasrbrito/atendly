# Atendly — roteiro de narração do vídeo

Texto narrado em cada cena do vídeo `Atendly - Apresentacao PI.mp4`, na ordem em que aparece. Pode ser usado para regravar a narração com a voz dos integrantes.

## Capa

Olá! Nós somos Brenda Marcondes dos Santos, Breno Henrique de Souza Lima e Bruna Suelen Ribeiro de Brito. Este é o Atendly: um sistema de acompanhamento de chamados e recuperação de soluções para suporte de TI, desenvolvido no Projeto Integrador da UNIVESP.

## Introdução

O Projeto Integrador tem como objetivo aplicar, na prática, os conhecimentos do curso para resolver problemas reais. O grupo escolheu como comunidade externa uma equipe de suporte de TI, que atende os colaboradores por meio de chamados abertos em um sistema de terceiros, que a própria equipe não pode alterar. Diante desse cenário, propusemos o Atendly: um sistema web que funciona ao lado do sistema de origem, recebendo uma cópia de cada chamado por integração.

## O Problema

Nas conversas iniciais, ficaram evidentes duas dificuldades. A primeira: os chamados novos demoram a ser percebidos, porque o sistema de origem não emite nenhum aviso, e esse atraso nem aparece nos registros. A segunda: as soluções de problemas já resolvidos ficam espalhadas em conversas de WhatsApp ou na memória dos colegas mais experientes. Na ausência dessa pessoa, o diagnóstico é refeito do zero, o que gera retrabalho.

## Objetivos

Nosso objetivo geral é desenvolver e disponibilizar em nuvem um sistema web que apoie a equipe no acompanhamento dos chamados, reduzindo o tempo até a percepção de novos chamados e facilitando a recuperação de soluções já aplicadas. Para isso, os objetivos específicos incluem: receber os chamados automaticamente por webhook; avisar a equipe em tempo real; construir uma base de conhecimento com busca por relevância; oferecer um painel de indicadores; garantir a acessibilidade; e validar tudo com testes automatizados.

## Fundamentação Teórica

A solução se apoia em referências da área. A ITIL 4 e a norma ISO 20000 tratam da medição do ciclo do chamado. A metodologia KCS propõe capturar o conhecimento durante o próprio atendimento. A busca de texto completo do MySQL ordena os resultados por relevância. Nos indicadores, usamos a mediana e o percentil 90, menos sensíveis a valores extremos. E a interface segue as diretrizes WCAG 2.1, nível AA, enquanto as entrevistas respeitam a LGPD.

## Solução Proposta

Assim surgiu o Atendly. Ele recebe os chamados por integração e avisa a equipe com aviso na tela, som, notificação do navegador e contador na aba, medindo o tempo até que um técnico assuma cada chamado. Durante o atendimento, sugere soluções semelhantes já aplicadas. E, para encerrar, exige a descrição da solução, que passa a alimentar a busca e a base de conhecimento.

## Tecnologias Utilizadas

No desenvolvimento, utilizamos PHP 8.4 com o framework Laravel 13 e templates Blade; banco de dados MySQL, com MariaDB no servidor; JavaScript em módulos, sem framework; Sass para os estilos; e Chart.js para os gráficos. O sistema consome a BrasilAPI de feriados nacionais, é versionado no GitHub, validado por 45 testes automatizados e está publicado na nuvem, na plataforma alwaysdata.

## Metodologia

A metodologia seguiu a pesquisa aplicada, de abordagem qualitativa e quantitativa e caráter exploratório. Elaboramos um roteiro de entrevistas sem identificação dos participantes. E, como não houve autorização para usar chamados reais, criamos uma base sintética com cerca de quinhentos chamados distribuídos em noventa dias. Por isso, os indicadores que vamos mostrar servem para validar o protótipo, e não como evidência do funcionamento real do suporte.

## Demonstração — abertura

Agora, vamos ver o sistema em funcionamento.

## Demonstração — Login

O acesso é restrito à equipe e exige login. Também há redefinição de senha por e-mail, com um link de uso único.

## Demonstração — Fila de chamados

A fila de chamados é a tela principal do técnico. Os chamados ainda não assumidos aparecem no topo, com o tempo de espera atualizado ao vivo, em vermelho quando passa da meta. Como hoje é domingo, o registro manual fica bloqueado, de acordo com o calendário de dias úteis da BrasilAPI.

## Demonstração — Novo chamado via integração

Neste momento, o sistema de origem envia um chamado novo pela integração. Em poucos segundos, o Atendly mostra o aviso na tela, toca um som e atualiza o contador na aba. Ao atualizar a fila, o chamado aparece no topo, aguardando um técnico.

## Demonstração — Atendimento e soluções semelhantes

O técnico assume o chamado. Na tela de atendimento, a linha do tempo mostra cada etapa do ciclo e o tempo até a percepção. No painel lateral, aparecem soluções semelhantes, com os termos destacados.

## Demonstração — Usar solução e encerrar

Com um clique em Usar esta solução, o artigo da base é aplicado e a solução é pré-preenchida. Para encerrar o chamado, a descrição da solução é obrigatória.

## Demonstração — Chamado encerrado

Depois de encerrado, o chamado fica somente leitura. Se o problema voltar, é possível abrir um novo chamado relacionado.

## Demonstração — Busca de soluções

A tela de busca responde à pergunta: como resolvemos isso antes? O técnico descreve o problema com as palavras do solicitante e recebe primeiro as soluções mais parecidas, da base de conhecimento e dos chamados encerrados.

## Demonstração — Base de conhecimento

A base de conhecimento reúne os artigos, ordenados pelos mais reaproveitados, e indica as soluções de chamados que ainda podem ser consolidadas em artigos.

## Demonstração — Painel de indicadores (dados sintéticos)

O painel de indicadores mostra o tempo até a percepção, com mediana e percentil 90, o percentual acima da meta, a taxa de resolução com uso da base e a percepção por horário e por dia da semana. Lembrando: esses valores foram calculados sobre dados sintéticos.

## Demonstração — Entrevistas sem identificação

Por fim, o módulo de entrevistas registra o diagnóstico sem dados identificáveis: cada participante tem apenas um código e um perfil genérico, e e-mail, telefone e CPF são bloqueados nas anotações.

## Demonstração — Acessibilidade e tema escuro

A interface também oferece tema escuro, navegação por teclado e foco visível, e não apresentou violações na auditoria de acessibilidade WCAG 2.1 nível AA.

## Conclusão

Os resultados parciais mostram que a solução é tecnicamente viável: o sistema está publicado em nuvem, integra-se por webhook, consome a BrasilAPI, atende à WCAG 2.1 nível AA e é verificado por testes automatizados. Como limitação, os indicadores ainda foram calculados sobre dados sintéticos. As próximas etapas incluem conectar o Atendly ao sistema real, realizar as entrevistas e comparar os tempos de percepção antes e depois da adoção do sistema.

## Encerramento

Obrigado pela atenção!
