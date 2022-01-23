<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class ResultSet extends Vertex
{
    public function __construct(int $id)
    {
        parent::__construct($id, Vertex::LABEL_RESULT_SET);
    }
}
