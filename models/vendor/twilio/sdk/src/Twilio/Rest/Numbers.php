<?php

namespace Twilio\Rest;

use Twilio\Rest\Numbers\V2;

class Numbers extends NumbersBase {
    /**
     * @deprecated Use v2->regulatoryCompliance instead.
     */
    protected function getRegulatoryCompliance(): \Twilio\Rest\Numbers\V2\RegulatoryComplianceList {
        echo "regulatoryCompliance is deprecated. Use v2->regulatoryCompliance instead.";
        return $this->v2->regulatoryCompliance;
    }

<<<<<<< HEAD
}
=======
}
>>>>>>> b0fb1e9 (Harmonisation de la structure (pluriel) pour alignement avec branche compte)
