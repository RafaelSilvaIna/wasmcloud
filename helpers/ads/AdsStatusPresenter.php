<?php
declare(strict_types=1);

namespace Helpers\Ads;

final class AdsStatusPresenter
{
    public static function label(string $status): string
    {
        return match ($status) {
            'draft' => 'Rascunho',
            'awaiting_payment' => 'Aguardando pagamento',
            'pending_review' => 'Em fila de revisão',
            'in_review' => 'Em análise',
            'changes_requested' => 'Ajustes solicitados',
            'approved' => 'Aprovado',
            'active' => 'Ativo',
            'paused' => 'Pausado',
            'rejected' => 'Rejeitado',
            'finished' => 'Encerrado',
            default => 'Indefinido',
        };
    }

    public static function tone(string $status): string
    {
        return match ($status) {
            'draft' => 'neutral',
            'awaiting_payment' => 'warning',
            'pending_review' => 'info',
            'in_review' => 'info',
            'changes_requested' => 'warning',
            'approved' => 'success',
            'active' => 'success',
            'paused' => 'muted',
            'rejected' => 'danger',
            'finished' => 'muted',
            default => 'neutral',
        };
    }

    public static function journey(string $status): array
    {
        $order = ['created', 'submitted', 'review', 'approved', 'live'];
        $current = match ($status) {
            'draft' => 'created',
            'awaiting_payment' => 'submitted',
            'pending_review', 'in_review', 'changes_requested', 'rejected' => 'review',
            'approved' => 'approved',
            'active', 'paused', 'finished' => 'live',
            default => 'created',
        };
        $labels = [
            'created' => 'Criado',
            'submitted' => 'Enviado',
            'review' => 'Revisão',
            'approved' => 'Aprovado',
            'live' => 'Exibição',
        ];
        $currentIndex = array_search($current, $order, true);
        $steps = [];
        foreach ($order as $index => $key) {
            $state = $index < $currentIndex ? 'done' : ($index === $currentIndex ? 'active' : 'waiting');
            if ($key === 'review' && $status === 'changes_requested') {
                $state = 'attention';
            }
            if ($key === 'review' && $status === 'rejected') {
                $state = 'blocked';
            }
            $steps[] = [
                'key' => $key,
                'label' => $labels[$key],
                'state' => $state,
            ];
        }

        return [
            'steps' => $steps,
            'progress' => match ($status) {
                'draft' => 10,
                'awaiting_payment' => 25,
                'pending_review' => 45,
                'in_review' => 58,
                'changes_requested' => 58,
                'approved' => 82,
                'active', 'paused', 'finished' => 100,
                'rejected' => 58,
                default => 0,
            },
            'summary' => match ($status) {
                'draft' => 'O anúncio ainda está em preparação.',
                'awaiting_payment' => 'O criativo foi enviado e aguarda pagamento para seguir.',
                'pending_review' => 'O anúncio entrou na fila de revisão do PipoCine.',
                'in_review' => 'Um administrador está analisando o criativo.',
                'changes_requested' => 'A revisão pediu ajustes antes da publicação.',
                'approved' => 'O anúncio foi aprovado e aguarda publicação.',
                'active' => 'O anúncio está liberado para exibição.',
                'paused' => 'O anúncio já foi exibido, mas está pausado no momento.',
                'rejected' => 'O anúncio foi rejeitado na revisão.',
                'finished' => 'A campanha foi encerrada.',
                default => 'Status em processamento.',
            },
        ];
    }
}
