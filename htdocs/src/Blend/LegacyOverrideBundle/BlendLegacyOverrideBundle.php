<?php

namespace Blend\LegacyOverrideBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BlendLegacyOverrideBundle extends Bundle
{
    public function getParent()
    {
        return "EzPublishLegacyBundle";
    }
}
