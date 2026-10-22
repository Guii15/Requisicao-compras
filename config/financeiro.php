<?php

return [
    /*
     * Cada folha de cheque precisa ficar ABAIXO deste valor (em reais). Um pagamento maior é
     * desdobrado em folhas de valores iguais. Para mudar, defina FINANCEIRO_LIMITE_CHEQUE no .env.
     */
    'limite_cheque' => (float) env('FINANCEIRO_LIMITE_CHEQUE', 5000),
];
