<?php

class Vec2 {
    public
        $x,
        $y
    ;

    public function __construct(float $x, float $y) {
        $this->x = $x;
        $this->y = $y;
    }

    public static function add(Vec2 $a, Vec2 $b) {
        return new self(
            $a->x + $b->x,
            $a->y + $b->y
        );
    }

    public static function sub(Vec2 $a, Vec2 $b) {
        return new self(
            $a->x - $b->x,
            $a->y - $b->y
        );
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Line2 {
    private
        $a,
        $b
    ;
    public function a() : Vec2 {
        return $this->a;
    }

    public function b() : Vec2 {
        return $this->b;
    }

    public function __construct(Vec2 $a, Vec2 $b) {
        $this->a = $a;
        $this->b = $b;
    }

    public function side(Vec2 $c) {
        return
            (($this->b->x - $this->a->x) * ($c->y - $this->a->y)) -
            (($this->b->y - $this->a->y) * ($c->x - $this->a->x));
    }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class ViewField {
    private
        $position,
        $viewLeft,
        $viewRight
    ;

    public function __construct() {
        $this->viewLeft  = new Vec2(0, 0);
        $this->viewRight = new Vec2(0, 0);
    }

    public function update(Vec2 $position, Vec2 $direction, float $fieldOfView) {
        $this->position = $position;
        // Define Theta to be half the field of view, in radians.
        $fTheta         = (M_PI * $fieldOfView)/360;
        $fSinTheta      = sin($fTheta);
        $fCosTheta      = cos($fTheta);

        $this->viewLeft->x = $direction->x * $fCosTheta - $direction->y * $fSinTheta;
        $this->viewLeft->y = $direction->y * $fCosTheta + $direction->x * $fSinTheta;

        $fSinTheta = -$fSinTheta;

        $this->viewRight->x = $direction->x * $fCosTheta - $direction->y * $fSinTheta;
        $this->viewRight->y = $direction->y * $fCosTheta + $direction->x * $fSinTheta;
    }
}

$viewField = new ViewField();

$viewField->update(new Vec2(10, 10), new Vec2(0, 1), 90);

var_dump($viewField);

