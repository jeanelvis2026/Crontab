<?php
namespace CronManager\Helpers;

/**
 * Utilitários para expressões cron
 */
class CronHelper
{
    /**
     * Monta a expressão cron a partir dos campos individuais
     */
    public static function montar(string $minuto, string $hora, string $dia, string $mes, string $diaSemana): string
    {
        return trim("$minuto $hora $dia $mes $diaSemana");
    }

    /**
     * Valida cada campo da expressão cron.
     * Retorna array ['valido' => bool, 'erros' => ['campo' => 'mensagem']]
     */
    public static function validar(string $minuto, string $hora, string $dia, string $mes, string $diaSemana): array
    {
        $erros = [];

        $regras = [
            'minuto'     => ['valor' => $minuto,     'min' => 0,  'max' => 59,  'label' => 'Minuto'],
            'hora'       => ['valor' => $hora,        'min' => 0,  'max' => 23,  'label' => 'Hora'],
            'dia'        => ['valor' => $dia,         'min' => 1,  'max' => 31,  'label' => 'Dia'],
            'mes'        => ['valor' => $mes,         'min' => 1,  'max' => 12,  'label' => 'Mês'],
            'dia_semana' => ['valor' => $diaSemana,   'min' => 0,  'max' => 7,   'label' => 'Dia da semana'],
        ];

        foreach ($regras as $campo => $regra) {
            $erro = self::validarCampo($regra['valor'], $regra['min'], $regra['max'], $regra['label']);
            if ($erro) {
                $erros[$campo] = $erro;
            }
        }

        return ['valido' => empty($erros), 'erros' => $erros];
    }

    /**
     * Valida um campo individual da expressão cron
     */
    private static function validarCampo(string $valor, int $min, int $max, string $label): ?string
    {
        if ($valor === '') {
            return "$label não pode ser vazio.";
        }
        if ($valor === '*') {
            return null;
        }

        // Suporte a lista: 1,2,3
        $partes = explode(',', $valor);
        foreach ($partes as $parte) {
            $parte = trim($parte);

            // Step: */n ou n-m/n
            if (str_contains($parte, '/')) {
                [$range, $step] = explode('/', $parte, 2);
                if (!is_numeric($step) || (int)$step < 1) {
                    return "$label: passo inválido em '$parte'.";
                }
                if ($range !== '*') {
                    $erro = self::validarRange($range, $min, $max, $label);
                    if ($erro) return $erro;
                }
                continue;
            }

            // Range: n-m
            if (str_contains($parte, '-')) {
                $erro = self::validarRange($parte, $min, $max, $label);
                if ($erro) return $erro;
                continue;
            }

            // Valor simples
            if (!is_numeric($parte) || (int)$parte < $min || (int)$parte > $max) {
                return "$label: valor '$parte' fora do intervalo permitido ($min–$max).";
            }
        }

        return null;
    }

    private static function validarRange(string $range, int $min, int $max, string $label): ?string
    {
        if (!str_contains($range, '-')) {
            return null;
        }
        [$inicio, $fim] = explode('-', $range, 2);
        if (!is_numeric($inicio) || !is_numeric($fim)) {
            return "$label: range '$range' inválido.";
        }
        if ((int)$inicio < $min || (int)$fim > $max || (int)$inicio > (int)$fim) {
            return "$label: range '$range' fora do intervalo ($min–$max).";
        }
        return null;
    }

    /**
     * Calcula a próxima execução a partir de agora
     * Retorna um objeto DateTime ou null se não encontrar em 1 ano
     */
    public static function proximaExecucao(string $minuto, string $hora, string $dia, string $mes, string $diaSemana): ?\DateTime
    {
        $agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $agora->modify('+1 minute');
        $agora->setTime((int)$agora->format('H'), (int)$agora->format('i'), 0);

        $limite = (clone $agora)->modify('+1 year');

        while ($agora <= $limite) {
            if (
                self::campoMatch($agora->format('n'), $mes)    &&
                self::campoMatch($agora->format('j'), $dia)    &&
                self::campoMatch($agora->format('G'), $hora)   &&
                self::campoMatch($agora->format('i'), $minuto) &&
                self::campoMatch($agora->format('w'), $diaSemana)
            ) {
                return $agora;
            }
            $agora->modify('+1 minute');
        }

        return null;
    }

    /**
     * Verifica se um valor numérico satisfaz um campo cron
     */
    public static function campoMatch(string $valor, string $campo): bool
    {
        if ($campo === '*') return true;

        $v = (int)$valor;

        foreach (explode(',', $campo) as $parte) {
            $parte = trim($parte);

            if (str_contains($parte, '/')) {
                [$range, $step] = explode('/', $parte, 2);
                $step = (int)$step;
                if ($range === '*') {
                    if ($step > 0 && $v % $step === 0) return true;
                } elseif (str_contains($range, '-')) {
                    [$ini, $fim] = array_map('intval', explode('-', $range));
                    if ($v >= $ini && $v <= $fim && ($v - $ini) % $step === 0) return true;
                }
                continue;
            }

            if (str_contains($parte, '-')) {
                [$ini, $fim] = array_map('intval', explode('-', $parte));
                if ($v >= $ini && $v <= $fim) return true;
                continue;
            }

            if (is_numeric($parte) && (int)$parte === $v) return true;
        }

        return false;
    }

    /**
     * Formata duração em ms para string legível
     */
    public static function formatarDuracao(?int $ms): string
    {
        if ($ms === null) return '—';
        if ($ms < 1000) return "{$ms}ms";
        $s = round($ms / 1000, 2);
        if ($s < 60) return "{$s}s";
        $m = floor($s / 60);
        $r = round($s - $m * 60, 1);
        return "{$m}m {$r}s";
    }

    /**
     * Descrição legível de uma expressão cron
     */
    public static function descricao(string $minuto, string $hora, string $dia, string $mes, string $diaSemana): string
    {
        if ($minuto === '*' && $hora === '*' && $dia === '*' && $mes === '*' && $diaSemana === '*') {
            return 'A cada minuto';
        }
        if ($minuto === '0' && $hora === '*') return 'A cada hora';
        if ($minuto === '0' && $hora !== '*' && $dia === '*' && $mes === '*' && $diaSemana === '*') {
            return "Diariamente às {$hora}:00";
        }
        if ($minuto === '0' && $hora !== '*' && $diaSemana !== '*') {
            $dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
            $d = is_numeric($diaSemana) ? ($dias[(int)$diaSemana] ?? $diaSemana) : $diaSemana;
            return "Toda {$d} às {$hora}:00";
        }
        if (str_starts_with($minuto, '*/')) {
            $n = substr($minuto, 2);
            return "A cada {$n} minutos";
        }
        return self::montar($minuto, $hora, $dia, $mes, $diaSemana);
    }
}
