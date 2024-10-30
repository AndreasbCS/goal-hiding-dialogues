<?php

class Qbaf {
	public $arguments = []; // set X
	public $attacks = []; // binary relation R^-
	public $supports = []; // binary relation R^+
	public $baseScores = []; // total function tau

	/*
	Example constructor input:

	$arguments = ["a", "b", "c"];
	$attacks = [
		["a", "b"],
		["b", "c"]
	];
	$supports = [
		["a", "c"]
	];
	$baseScores = [
		"a" => 2,
		"b" => 3,
		"c" => 1
	];

	Example call:
	$qbaf = new Qbaf($arguments, $attacks, $supports, $baseScores);
	*/
	public function __construct($arguments = [], $attacks = [], $supports = [], $baseScores = []) {
		$this->arguments = $arguments;
		$this->attacks = $attacks;
		$this->supports = $supports;
		$this->baseScores = $baseScores;
	}

	/* Example json input:
	{
		"arguments": ["a", "b", "c"],
		"attacks": [
			["a", "b"],
			["b", "c"]
		],
		"supports": [
			["a", "c"]
		],
		"baseScores": {
			"a": 2,
			"b": 3,
			"c": 1
		}
	}

	Example call:
	$qbaf = Qbaf::fromJson($jsonString);
	*/
	public static function qbafFromJson($jsonString) {
		$jsonData = json_decode($jsonString, true);

		$arguments = $jsonData['arguments'];
		$attacks = $jsonData['attacks'];
		$supports = $jsonData['supports'];
		$baseScores = $jsonData['baseScores'];

		return new self($arguments, $attacks, $supports, $baseScores);
	}

	public function addArgument($arg, $score) {
		$this->arguments[] = $arg;
		$this->baseScores[$arg] = $score;
		$this->attacks[$arg] = [];
		$this->supports[$arg] = [];
	}

	public function addAttack($attacker, $target) {
		$this->attacks[$target][] = $attacker;
	}

	public function addSupport($supporter, $target) {
		$this->supports[$target][] = $supporter;
	}

	public function hasArgument($arg = NULL) {
		if($arg != NULL && in_array($arg, $this->arguments)){
			return true;
		}
		else{
			return false;
		}
	}

	public function getArgumentsKeys() {
		return $this->arguments;
	}

	public function getArgumentsObjects($arg = NULL) {
		$arguments_data = array();
		
		if($arg != NULL && in_array($arg, $this->arguments)){
	
			$baseScore = 0;
			$attacks = array();
			$supports = array();

			if(isset($this->baseScores[$arg])){
				$baseScore = $this->baseScores[$arg];
			}	
			if(isset($this->attacks[$arg])){
				$attacks = $this->attacks[$arg];
			}
			if(isset($this->supports[$arg])){
				$supports = $this->supports[$arg];
			}			
			
			array_push($arguments_data, array(	
							"argument" => $arg, 
							"baseScore" => $baseScore, 
							"attacks" => $attacks, 
							"supports" => $supports
						));
		}
		else{		
			if(!empty($this->arguments)){
				foreach($this->arguments as $arg){
					
					$baseScore = 0;
					$attacks = array();
					$supports = array();

					if(isset($this->baseScores[$arg])){
						$baseScore = $this->baseScores[$arg];
					}	
					if(isset($this->attacks[$arg])){
						$attacks = $this->attacks[$arg];
					}
					if(isset($this->supports[$arg])){
						$supports = $this->supports[$arg];
					}					
					
					array_push($arguments_data, array(	
								"argument" => $arg, 
								"baseScore" => $baseScore, 
								"attacks" => $attacks, 
								"supports" => $supports
							));
				}				
			}
		}
		return $arguments_data;
	}
	
    public function getAttacks($arg = NULL) {
        if($arg != NULL){			
			if(isset($this->attacks[$arg])){
				return $this->attacks[$arg];
			}
			else{
				return array();
			}
		}
		else{
			return $this->attacks;
		}  	
    }

    public function getSupports($arg = NULL) {
        if($arg != NULL){
			if(isset($this->supports[$arg])){
				return $this->supports[$arg];
			}
			else{
				return array();
			}
		}
		else{
			return $this->supports;
		}
	}

    public function getBaseScore($arg = NULL) {
       if($arg != NULL){		
			if(isset($this->baseScores[$arg])){
				return $this->baseScores[$arg];
			}
			else{
				return 0;
			}
		}
		else{
			return $this->baseScores;
		}  		
    }	
	
	// Function that checks if A supports B.
	public function hasSupport($topicA, $topicB) {
		
		if(isset($this->supports[$topicB])){
			$supports_to_topicB = $this->supports[$topicB];
			if(isset($supports_to_topicB[$topicA])){
				return true;
			}
			else{
				return false;
			}			
		}
		return false;
	}	
	
	// Function that checks if A attacks B.
	public function hasAttack($topicA, $topicB) {
		
		if(isset($this->attacks[$topicB])){
			$attacks_to_topicB = $this->attacks[$topicB];
			if(isset($attacks_to_topicB[$topicA])){
				return true;
			}
			else{
				return false;
			}
		}
	}
}

class QbafManager {
	private $qbafs; // an array of Qbaf objects representing different states
	private $discount; // discount constant determining speed of influence of willingness
	private $dialogueLog; // a log which is populated through the dialogue process

	public function __construct($qbafs = [], $discount = 0.1, $dialogueLog) {
		$this->qbafs = $qbafs;
		$this->discount = $discount;
        $this->dialogueLog = $dialogueLog;
	}	
	
	public function addQbaf(Qbaf $qbaf) {
		$this->qbafs[] = $qbaf;
	}

	public function getQbaf($stateIndex) {
		if(isset($this->qbafs[$stateIndex])){
			return $this->qbafs[$stateIndex];
		}
		else{
			return NULL;
		}
	}

	public function setDiscount($discount) {
		$this->discount = $discount;
	}

	/* This function calculated strengths of an argument based on its attackers and supporters 
	 * (This function will call a logic program to calculate strengths of arguments)
	***/
	public function calculateStrength($qbaf, $arg, $newRelationsCount) {
			
		
			
		// TODO: 
		// Check if the number of supports/attacks have changed for the argument. 
		// If it is the same amount as last dialogueState, then make no change.		
				
		//$qbaf = $this->getQbaf($stateIndex);
		//$qbaf_prev = $this->getQbaf($stateIndex-1);
				
		$numAttacks = count($qbaf->getAttacks($arg));
		$numSupports = count($qbaf->getSupports($arg));

		//$numAttacks_prev = count($qbaf_prev->getAttacks($arg));
		//$numSupports_prev = count($qbaf_prev->getSupports($arg));

		$strength = $qbaf->getBaseScore($arg);

		//if($newRelationsCount > 0){
			if($numAttacks > $numSupports){
				$strength = $qbaf->getBaseScore($arg) - $this->discount * ($numAttacks - $numSupports);
			}

			if($numAttacks < $numSupports){
				$strength = $qbaf->getBaseScore($arg) + $this->discount * ($numSupports - $numAttacks);
			}			
		//}

		return max(0, min(1, $strength)); // strength is in [0,1]
	}

	/*
	* This function takes an argument $arg and calculates its strength in each Qbaf object stored in the $qbafs array of the QbafManager.
	***/
	public function getArgumentStrengthTrajectory($arg) {
		$strengths = [];
		$stateIndex = 0;
		foreach ($this->qbafs as $qbaf) {
			
			$newRelationsCount = $this->newRelations($arg, $stateIndex-1, $stateIndex);
				
			$strengths[] = $this->calculateStrength($qbaf, $arg, $newRelationsCount);
			$stateIndex++;
		}
		return $strengths;
	}

	/*
	public function compareArguments($arg1, $arg2) {
		$strengths1 = $this->getArgumentStrengthTrajectory($arg1);
		$strengths2 = $this->getArgumentStrengthTrajectory($arg2);

		$max1 = max($strengths1);
		$max2 = max($strengths2);

		if($max1 == $max2){
			return 0; // arg1 and arg2 have the same strength across all states
		}

		return ($max1 > $max2) ? 1 : -1; // arg1 is stronger (1) or weaker (-1) than arg2
	}*/

	public function checkArgumentStrengthChange($arg, $stateIndex1, $stateIndex2) {
		$qbaf1 = $this->qbafs[$stateIndex1];
		$qbaf2 = $this->qbafs[$stateIndex2];

		$newRelationsCount1 = $this->newRelations($arg, $stateIndex1-1, $stateIndex1);
		$newRelationsCount2 = $this->newRelations($arg, $stateIndex2-1, $stateIndex2);

		$strength1 = $this->calculateStrength($qbaf1, $arg, $newRelationsCount1);
		$strength2 = $this->calculateStrength($qbaf2, $arg, $newRelationsCount2);

		if ($strength1 == $strength2) {
			return 0; // arg has the same strength in both states
		}

		return ($strength1 > $strength2) ? 1 : -1; // arg is stronger (1) or weaker (-1) in state 1 compared to state 2
	}
	
	public function getStrengthScores($qbaf){
		
		$strengthScores = [];
		
		if(!empty($qbaf->getArgumentsObjects())){
			foreach ($qbaf->getArgumentsObjects() as $argumentData) {
				$arg = $argumentData['argument'];
				
				$argStrength = $this->calculateStrength($qbaf, $arg, 1);
				$strengthScores[$arg] = $argStrength;
			}
		}
		else{
			$strengthScores = $qbaf->getBaseScore();
		}
		
		return $strengthScores;
	}
	
	/**
    Adds a new Qbaf object to the QbafManager with the same arguments, attacks, and supports as the input Qbaf, 
	where baseScores of arguments are equal to the calculated strength of each argument in the input Qbaf. 
    @param Qbaf $qbaf The Qbaf object to be copied with updated baseScores
    @return void
    */		
	public function addIncrementedQbaf($qbaf, $newRelations) { //Qbaf $qbaf

		$newBaseScores = [];
		
		if($newRelations > 0){
			foreach ($qbaf->getArgumentsObjects() as $argumentData) {
				$arg = $argumentData['argument'];
				
				$strength = $this->calculateStrength($qbaf, $arg, $newRelations);
				$newBaseScores[$arg] = $strength;
			}
		}
		else{
			$newBaseScores = $qbaf->getBaseScore();
		}
		//$newQbaf = new Qbaf($qbaf->getArgumentsKeys(), $qbaf->getAttacks(), $qbaf->getSupports(), $newBaseScores); // No clear arguments, new basescores
		//$newQbaf = new Qbaf($qbaf->getArgumentsKeys(), [], [], $newBaseScores); // Clear supports/attacks on each new state
		$newQbaf = new Qbaf($qbaf->getArgumentsKeys(), $qbaf->getAttacks(), $qbaf->getSupports(), $qbaf->getBaseScore()); // No clear arguments, original basescores
		
		$this->qbafs[] = $newQbaf;
	}	
	
	public function newRelations($arg, $stateIndex1, $stateIndex2){
		
		$newSupports = 0;
		$newAttacks = 0;
		$newRelationsCount = 0;
		
		if(isset($this->qbafs[$stateIndex1]) && isset($this->qbafs[$stateIndex2])){
			$qbaf1 = $this->qbafs[$stateIndex1];
			$qbaf2 = $this->qbafs[$stateIndex2];		
			
			$arg_supports1 = $qbaf1->getSupports($arg);
			$arg_attacks1 = $qbaf1->getAttacks($arg);

			$arg_supports2 = $qbaf2->getSupports($arg);
			$arg_attacks2 = $qbaf2->getAttacks($arg);

			if(count($arg_supports1) < count($arg_supports2)){
				$newSupports++;
				$newRelationsCount++;
			}
			if(count($arg_attacks1) < count($arg_attacks2)){
				$newAttacks++;
				$newRelationsCount++;
			}						
		}
		//return array("newSupports" => $newSupports, "newAttacks" => $newAttacks);
		return $newRelationsCount;
	}
}

?>