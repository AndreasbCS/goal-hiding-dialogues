<?php

class FormInputManager {
    private $dialogueScript;

    public function __construct($dialogueScriptJson) {
        $this->dialogueScript = json_decode($dialogueScriptJson, true);
    }

    public function processFormInput($formData) {
        $beliefs = [];

        foreach ($this->dialogueScript['dialogueScripts'] as $script) {
            if ($script['topic'] === $formData['topic']) {
                foreach ($script['script'] as $questionData) {
					
					$questionKey = $questionData['question'];
					$answerType = $questionData['answerType'];					
					$answers = $questionData['answers'];					
					
					foreach ($answers as $answerOption) {
						
						$beliefID = $answerOption['belief'];
						
						if(isset($formData[$beliefID])){							
							$answerValue = $formData[$beliefID];

							if ($answerType === 'radio') {
								if (!empty($answerValue)) {
									$beliefs[] = $answerValue;
								}
							} elseif ($answerType === 'checkbox') {
								if (is_array($answerValue)) {
									$beliefs = array_merge($beliefs, $answerValue);
								}
							} elseif ($answerType === 'freetext') {
								$matchedBeliefs = $this->matchFreetextBeliefs($questionData, $answerValue);
								$beliefs = array_merge($beliefs, $matchedBeliefs);
							}							
						}

					}
                }
                break; // No need to process other scripts
            }
        }
		
		$dialogue_moves = [];
		
		if(!empty($beliefs)){
			foreach($beliefs as $belief){
				$dialogue_moves[] = array("agent" => "respondent", "moveType" => "assert_belief", "utterance" => $belief);
			}
		}
		
        return $dialogue_moves;
    }

    private function matchFreetextBeliefs($questionData, $answerValue) {
        $matchedBeliefs = [];

        foreach ($questionData['answers'] as $answer) {
            similar_text($answerValue, $answer['text'], $similarity);
            if ($similarity >= 70) {
                $matchedBeliefs[] = $answer['belief'];
            }
        }

        return $matchedBeliefs;
    }
}

?>