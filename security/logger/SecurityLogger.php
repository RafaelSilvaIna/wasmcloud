<?php

declare(strict_types=1);

namespace Security\Logger;

use Security\Storage\DbSecurityStore;

/**
 * SecurityLogger — Log estruturado de eventos de segurança.
 *
 * Wrapper centralizado para registro em sec_threat_events e sec_incident_log.
 * Todos os métodos são fire-and-forget (tolerantes a falha).
 */
final class SecurityLogger
{
    public function __construct(private readonly DbSecurityStore $store) {}

    /**
     * Registra um evento de ameaça de baixa/média severidade.
     */
    public function event(
        string $ip,
        string $eventType,
        string $actionTaken,
        int    $threatScore,
        int    $scoreDelta,
        array  $context = []
    ): void {
        $severity = $this->severityFromScore($threatScore);
        $this->store->logThreatEvent(
            $ip,
            $eventType,
            $severity,
            $actionTaken,
            $threatScore,
            $scoreDelta,
            $context
        );
    }

    /**
     * Registra um incidente relevante com contexto completo.
     */
    public function incident(
        string $ip,
        string $incidentType,
        string $actionTaken,
        int    $mitigationLevel,
        int    $threatScore,
        array  $securityContext = []
    ): void {
        $severity = $this->severityFromLevel($mitigationLevel);
        $this->store->logIncident(
            $incidentType,
            $severity,
            $ip,
            $actionTaken,
            $mitigationLevel,
            $threatScore,
            $securityContext
        );
    }

    /**
     * Registra um bloqueio (ban/quarantine) com nível crítico.
     */
    public function block(string $ip, string $banType, int $threatScore, string $reason): void
    {
        $this->store->logThreatEvent(
            $ip,
            match ($banType) {
                'soft'   => 'soft_ban_applied',
                'hard'   => 'hard_ban_applied',
                'shadow' => 'shadow_ban_applied',
                default  => 'quarantine_entered',
            },
            'critical',
            match ($banType) {
                'soft'   => 'soft_banned',
                'hard'   => 'hard_banned',
                'shadow' => 'shadow_banned',
                default  => 'quarantined',
            },
            $threatScore,
            0,
            ['details' => ['reason' => $reason]]
        );
    }

    // -------------------------------------------------------------------------

    private function severityFromScore(int $score): string
    {
        return match (true) {
            $score >= 750 => 'critical',
            $score >= 500 => 'high',
            $score >= 250 => 'medium',
            $score >= 100 => 'low',
            default       => 'low',
        };
    }

    private function severityFromLevel(int $level): string
    {
        return match ($level) {
            5       => 'critical',
            4       => 'high',
            3       => 'medium',
            2       => 'low',
            default => 'info',
        };
    }
}
