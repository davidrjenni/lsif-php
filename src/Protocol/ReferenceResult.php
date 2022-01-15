<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class ReferenceResult extends Vertex
{

    public function __construct(int $id)
    {
        parent::__construct($id, Vertex::LABEL_REFERENCE_RESULT);
    }
}
