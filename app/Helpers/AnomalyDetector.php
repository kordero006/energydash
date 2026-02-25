<?php
namespace App\Helpers;

class AnomalyDetector {
    // La constante debe ir aquí, FUERA de la función
    public const SAFETY_LIMIT = 1000;

    public static function analyze($currentValue, $history) {
        $powers = array_column($history, 'power');
        
        // Estructura de respuesta inicial
        $res = [
            'is_anomaly' => false,
            'score' => 0,
            'mean' => 0,
            'reason' => 'Consumo Estable'
        ];

        // 1. REGLA DE ORO: Si supera los 1000W, es alerta inmediata
        if ($currentValue >= self::SAFETY_LIMIT) {
            $res['is_anomaly'] = true;
            $res['reason'] = "⚠️ SOBRECARGA: >" . self::SAFETY_LIMIT . "W";
            $res['score'] = 'MÁX';
            
            // Calculamos la media del pasado para contexto
            $past = array_slice($powers, 1);
            $res['mean'] = !empty($past) ? round(array_sum($past) / count($past), 2) : $currentValue;
            return $res;
        }

        // 2. LÓGICA DE IA (Z-Score) para consumos menores a 1000W
        if (count($powers) < 3) {
            $res['mean'] = $currentValue;
            return $res;
        }

        $pastValues = array_slice($powers, 1); // Excluir el dato actual
        $count = count($pastValues);
        $mean = array_sum($pastValues) / $count;

        $variance = 0;
        foreach ($pastValues as $p) $variance += pow($p - $mean, 2);
        $stdDev = sqrt($variance / $count);
        if ($stdDev < 20) $stdDev = 20; 

        $zScore = abs($currentValue - $mean) / $stdDev;
        
        $res['mean'] = round($mean, 2);
        $res['score'] = round($zScore, 2);

        // Si el Z-Score es alto, es una anomalía estadística
        if ($zScore > 2.5 && $currentValue > ($mean * 1.5)) {
            $res['is_anomaly'] = true;
            $res['reason'] = "⚠️ CONSUMO INUSUAL (IA)";
        }

        return $res;
    }
}