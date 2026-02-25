<?php
namespace App\Helpers;

class AnomalyDetector {
    public const SAFETY_LIMIT = 1000;

    public static function analyze($currentValue, $history) {
        // BLINDAJE 1: Asegurar que currentValue sea número y history sea un array
        $currentValue = is_numeric($currentValue) ? (float)$currentValue : 0;
        $history = is_array($history) ? $history : [];
        
        $powers = array_column($history, 'power');
        
        $res = [
            'is_anomaly' => false,
            'score' => 0,
            'mean' => 0,
            'reason' => 'Consumo Estable'
        ];

        // 1. REGLA DE ORO: Sobrecarga inmediata
        if ($currentValue >= self::SAFETY_LIMIT) {
            $res['is_anomaly'] = true;
            $res['reason'] = "⚠️ SOBRECARGA: >" . self::SAFETY_LIMIT . "W";
            $res['score'] = 'MAX';
            
            $past = array_slice($powers, 1);
            $res['mean'] = !empty($past) ? round(array_sum($past) / count($past), 2) : $currentValue;
            return $res;
        }

        // 2. LÓGICA DE IA (Z-Score)
        // BLINDAJE 2: Si no hay suficientes datos históricos, no calculamos Z-Score
        if (count($powers) < 3) {
            $res['mean'] = $currentValue;
            return $res;
        }

        $pastValues = array_slice($powers, 1);
        $count = count($pastValues);
        
        if ($count === 0) {
            $res['mean'] = $currentValue;
            return $res;
        }

        $mean = array_sum($pastValues) / $count;

        $variance = 0;
        foreach ($pastValues as $p) {
            $variance += pow((float)$p - $mean, 2);
        }
        
        $stdDev = sqrt($variance / $count);
        
        // Evitamos división por cero o valores insignificantes
        if ($stdDev < 20) $stdDev = 20; 

        // Fórmula matemática aplicada:
        // $$z = \frac{|x - \mu|}{\sigma}$$
        $zScore = abs($currentValue - $mean) / $stdDev;
        
        $res['mean'] = round($mean, 2);
        $res['score'] = round($zScore, 2);

        // Umbral de sensibilidad: Z > 2.5 y un aumento del 50% sobre la media
        if ($zScore > 2.5 && $currentValue > ($mean * 1.5)) {
            $res['is_anomaly'] = true;
            $res['reason'] = "⚠️ CONSUMO INUSUAL (IA)";
        }

        return $res;
    }
}