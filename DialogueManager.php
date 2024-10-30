<?php

class DialogueManager {
    private $topics;
    private $beliefs;
    private $qbafManager;
    private $dependencyGraph;
    private $discount;
    private $sensitivityInterval;
    private $dialogue = [];
	private $dialogueLog;

    public function __construct($topics, $beliefs, $qbafManager, $dependencyGraph, $discount, $sensitivityInterval, $dialogueLog) {
        $this->topics = $topics;
        $this->beliefs = $beliefs;
        $this->qbafManager = $qbafManager;
        $this->dependencyGraph = $dependencyGraph;
        $this->discount = $discount;
        $this->sensitivityInterval = $sensitivityInterval;
        $this->dialogueLog = $dialogueLog;
		
		$this->dialogue[] = ["agent" => "", "moveType" => "", "utterance" => ""]; // initiate with an empty move, which is coupled with the initial QBAF.
    }
	
	public function getDialogue(){
		return $this->dialogue;
	}

    public function addMove($agent, $utteranceType, $utterance) {
              
		// If the dialogue is empty, return 0 (first index)
		if(empty($this->dialogue)){
			$dialogueState = 0;
		}
		// otherwise, return the last index
		else{
			$dialogueState = count($this->dialogue)-1;
		}
		
		$qbaf = $this->qbafManager->getQbaf($dialogueState);

		$qbafNext = clone $qbaf;	
		$newTopicRelations = 0;
		
        if ($utteranceType === "open_topic") {
			// Check if the topic exists
            if (!in_array($utterance, $this->topics)) {
                throw new Exception("Topic ".$utterance." not found.");
            }
			
			// Check if the topic is within the sensitivity interval
			if($this->checkSensitivityInterval($utterance, $dialogueState) !== "within"){						
				throw new Exception("Topic ".$utterance." (Strength: ".$this->qbafManager->calculateStrength($qbaf, $utterance, 1).") is not within the sensitivity interval [".$this->sensitivityInterval['lowerBound'].", ".$this->sensitivityInterval['upperBound']."] in dialogue state ".$dialogueState.".");
			}
			
            $this->dialogue[] = ["agent" => $agent, "moveType" => $utteranceType, "utterance" => $utterance]; //[$agent, $utteranceType, $utterance];

		} elseif ($utteranceType === "close_topic") {
            if (!in_array($utterance, $this->topics)) {
                throw new Exception("Topic ".$utterance." is not found.");
            }

            $this->dialogue[] = [$agent, $utteranceType, $utterance];
        } elseif ($utteranceType === "assert_belief") {
            if (!in_array($utterance, $this->beliefs)) {
				return false;
            }

            $this->dialogue[] = ["agent" => $agent, "moveType" => $utteranceType, "utterance" => $utterance]; //[$agent, $utteranceType, $utterance];

			// Check for new relations in the next qbaf

            $positivePairs = $this->dependencyGraph->getPositiveTopicPairs($utterance);
            if(!empty($positivePairs)){
				foreach ($positivePairs as $pair) {
					$arg1 = $pair[0];
					$arg2 = $pair[1];
					if ($qbafNext->hasArgument($arg1) && $qbafNext->hasArgument($arg2)) {
						$qbafNext->addSupport($arg1, $arg2);
						
						$newTopicRelations++;
					}
				}
			}
            $negativePairs = $this->dependencyGraph->getNegativeTopicPairs($utterance);
            if(!empty($negativePairs)){
				foreach ($negativePairs as $pair) {
					$arg1 = $pair[0];
					$arg2 = $pair[1];
					if ($qbafNext->hasArgument($arg1) && $qbafNext->hasArgument($arg2)) {
						$qbafNext->addAttack($arg1, $arg2);
					
						$newTopicRelations++;
					}
				}
			}
		}
		
		// For an allowed move, add a new qbaf to the new dialogue state
		if ($utteranceType === "open_topic" || $utteranceType === "close_topic" || $utteranceType === "assert_belief") {
			$this->qbafManager->addIncrementedQbaf($qbafNext, $newTopicRelations);
			
			$this->dialogueLog->addDialogueState($dialogueState);
			$this->dialogueLog->addDialogue($dialogueState, $this->dialogue);
			$this->dialogueLog->addQbaf($dialogueState, $qbafNext);
			$this->dialogueLog->addAttacks($dialogueState, $qbafNext->getAttacks());
			$this->dialogueLog->addSupports($dialogueState, $qbafNext->getSupports());
			$this->dialogueLog->addTopicStrengths($dialogueState, $this->qbafManager->getStrengthScores($qbafNext));	
		}

	}

	// Ranks topics and selects the highest ranked topic
	public function selectTopic($goalTopic) {
		$topicsWithInInterval = []; // topics with strength within the sensitivity interval
		$topicsAboveInterval = []; // topics with strength above the sensitivity interval

		// If the dialogue is empty, return 0 (first index)
		if(empty($this->dialogue)){
			$dialogueState = 0;
		}
		// otherwise, return the last index
		else{
			$dialogueState = count($this->dialogue)-1;
		}

		$qbaf = $this->qbafManager->getQbaf($dialogueState);
		$arguments = $qbaf->getArgumentsKeys();

		// Collect all topics with strength within the sensitivity interval
		foreach($arguments as $arg) {

			if(in_array($arg, $this->topics)) {
				if($this->checkSensitivityInterval($arg, $dialogueState) === "within") {
					$topicsWithInInterval[] = $arg;
				} else if($this->checkSensitivityInterval($arg, $dialogueState) === "above") {
					$topicsAboveInterval[] = $arg;
				}
			}
		}
		
		// Rank the topics with strength WITHIN the interval.
		// The highest ones (closer to the interval upper bound) are more preferred.
		$highRankedTopics = [];
		foreach($topicsWithInInterval as $topic) {
	
			$newRelationsCount = $this->qbafManager->newRelations($topic, $dialogueState-1, $dialogueState);
			
			$strength = $this->qbafManager->calculateStrength($qbaf, $topic, $newRelationsCount);
			$score = 100 + $strength; // The highest ones are more preferred, hence plus strength.
			if($topic === $goalTopic) {
				$score += 1000; // If the goal topic is included, then select the goal topic.
			} else if($qbaf->hasSupport($topic, $goalTopic)) {
				$score += 50;
			} else if($qbaf->hasSupport($goalTopic, $topic)) {
				$score += 25;
			}
			$highRankedTopics[$topic] = $score;
		}
		arsort($highRankedTopics);
		
		// Rank the topics with strength above the interval.
		// The lowest ones (closer to the interval) are more preferred.
		$lowRankedTopics = [];
		foreach($topicsAboveInterval as $topic) {
		
			$newRelationsCount = $this->qbafManager->newRelations($topic, $dialogueState-1, $dialogueState);
			
			$strength = $this->qbafManager->calculateStrength($qbaf, $topic, $newRelationsCount);
						
			$score = 100 - $strength; // The lowest ones are more preferred, hence minus strength.
			if($topic === $goalTopic) {
				$score += 1000; // If the goal topic is included, then select the goal topic.
			} else if($qbaf->hasSupport($topic, $goalTopic)) {
				$score += 50;
			} else if($qbaf->hasSupport($goalTopic, $topic)) {
				$score += 25;
			}
			$lowRankedTopics[$topic] = $score;
		}
		arsort($lowRankedTopics);
		
		// Apply a bonus to topics with strength within the sensitivity interval
		foreach($highRankedTopics as $topic => $score) {
			if(array_key_exists($topic, $lowRankedTopics)) {
				$highRankedTopics[$topic] += 5;
			}
		}
		
		// Merge the ranked topics
		$highRankedTopics = array_merge($highRankedTopics, $lowRankedTopics);
			
		// Remove topics already discussed
		if(!empty($this->dialogue) && !empty($highRankedTopics)){
			
			foreach($this->dialogue as $move){

				foreach($highRankedTopics as $rtopic){
					if(array_key_exists($move['utterance'], $highRankedTopics)){
						unset($highRankedTopics[$move['utterance']]);
						break;
					}
				}
			}
		}
		
		// Get the topic key with the highest rank
		$selectedTopic = key($highRankedTopics);
		
		// Return the move with the selected topic
		return ["agent" => "seeker", "moveType" => "open_topic", "utterance" => $selectedTopic];
	}
	
	public function checkSensitivityInterval($arg, $dialogueState = NULL) {

		// If the dialogue is empty, return 0 (first index)
		if(empty($this->dialogue)){
			$dialogueState = 0;
		}
		// otherwise, return the last index
		else{
			$dialogueState = count($this->dialogue)-1;
		}
		
        $qbaf = $this->qbafManager->getQbaf($dialogueState);

		// TODO: if no new relation to the topic, then strength is basescore, otherwise check calculate strength
		$newRelationsCount = $this->qbafManager->newRelations($arg, $dialogueState-1, $dialogueState);
			
		$strength = $this->qbafManager->calculateStrength($qbaf, $arg, $newRelationsCount);		
		
		if ($strength >= $this->sensitivityInterval['lowerBound'] && $strength <= $this->sensitivityInterval['upperBound']) {
			return "within"; // argument strength is within the sensitivity interval
		} 
		if ($strength > $this->sensitivityInterval['upperBound']) {
			return "above"; // argument strength is above the sensitivity interval
		} 
		else {
			return "below"; // argument strength is below the sensitivity interval
		}
	}	
	
	// Example JSON input as an array
	/*
	$moves = [
		["agent" => "seeker", "moveType" => "open_topic", "utterance" => "t0"],
		["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b0"],
		["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b1"],
		["agent" => "seeker", "moveType" => "open_topic", "utterance" => "t2"],
		["agent" => "respondent", "moveType" => "assert_belief", "utterance" => "b2"],
	];*/	
	public function processDialogue($moves, $print = TRUE) {
		if(!empty($moves)){
			foreach ($moves as $move) {
				$agent = $move["agent"];
				$moveType = $move["moveType"];
				$utterance = $move["utterance"];

				try{
					$this->addMove($agent, $moveType, $utterance);
					if($print == TRUE){
						$this->dialogueLog->createLog();
					}
				}
				catch(Exception $e){
					if($print == TRUE){
						$this->dialogueLog->addToLogMessage($e->getMessage());
					}
					return false;
					exit;
				}
			}			
		}
	}


}
?>