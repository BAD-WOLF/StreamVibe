<?php
declare(strict_types = 1);
namespace App\Application\Movie\DTO\Credits\Response;

use ApiPlatform\Metadata\ApiProperty;
use App\Application\Movie\DTO\Credits\Response\Model\CastMember;
use App\Application\Movie\DTO\Credits\Response\Model\CrewMember;
use Symfony\Component\Serializer\Attribute\Ignore;

final class GetMovieCreditsResponse {
    /**
     * NOTA (bug corrigido): nativeType removido (ver comentário
     * anterior — expressão constante).
     *
     * @param int $id
     * @param array<CastMember> $cast
     * @param array<CrewMember> $crew
     */
    public function __construct(
        #[ApiProperty]
        public private(set) int $id {
            get => $this->id;
        },
        #[ApiProperty]
        public private(set) array $cast {
            get => $this->cast;
        },
        #[ApiProperty]
        public private(set) array $crew {
            get => $this->crew;
        },
    ) {
    }

    /* ============================
     *  Virtual / Computed Properties
     *  NOTA (bug corrigido): #[Ignore] adicionado em todas — sem
     *  isso, cada property hook é uma propriedade pública comum pro
     *  Reflection, e o gerador de schema do API Platform as
     *  enumerava junto com id/cast/crew, poluindo o contrato público
     *  com getters de uso interno (e com tipo errado, já que
     *  array<CastMember> num @return de property hook não é
     *  resolvido do mesmo jeito que num @param de construtor).
     * ============================ */
    #[Ignore]
    public bool $hasCast {
        get => !empty($this->cast);
    }
    #[Ignore]
    public bool $hasCrew {
        get => !empty($this->crew);
    }
    #[Ignore]
    public int $castCount {
        get => count($this->cast);
    }
    #[Ignore]
    public int $crewCount {
        get => count($this->crew);
    }
    #[Ignore]
    public bool $hasCredits {
        get => $this->hasCast || $this->hasCrew;
    }

    /**
     * @return array<CastMember>
     */
    #[Ignore]
    public array $mainCast {
        get => array_values(
            array_filter(
                $this->cast,
                static fn(CastMember $member) => $member->order !== null && $member->order <= 10
            )
        );
    }

    /**
     * @return array<CrewMember>
     */
    #[Ignore]
    public array $directors {
        get => array_values(
            array_filter(
                $this->crew,
                static fn(CrewMember $member) => strtolower($member->job) === 'director'
            )
        );
    }

    /**
     * @return array<string, array<CrewMember>>
     */
    #[Ignore]
    public array $crewByDepartment {
        get {
            $grouped = [];
            foreach ($this->crew as $member) {
                $grouped[$member->department][] = $member;
            }
            ksort($grouped);
            return $grouped;
        }
    }
    #[Ignore]
    public bool $hasDirector {
        get => !empty($this->directors);
    }

    #[Ignore]
    public bool $hasValidData {
        get =>
            $this->id > 0 &&
            $this->hasCredits;
    }

    #[Ignore]
    public array $asArray {
        get => [
            'id' => $this->id,
            'cast_count' => $this->castCount,
            'crew_count' => $this->crewCount,
            'has_director' => $this->hasDirector,
            'cast' => $this->cast,
            'crew' => $this->crew,
            'directors' => $this->directors,
        ];
    }

    /**
     * Sempre true — falhas reais (filme não encontrado, erro externo)
     * são lançadas como exceção pelo GetMovieCreditsUseCase antes desta
     * classe ser instanciada, seguindo o mesmo padrão de
     * GetMovieDetailsResponse::isSuccess().
     *
     * @return bool
     */
    public function isSuccess(): bool {
        return true;
    }
}
