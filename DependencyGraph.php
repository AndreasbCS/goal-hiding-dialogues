<?php

class DependencyGraph {
    public $beliefs;
    public $topics;
    public $edges;

    public function __construct($beliefs, $topics, $edges) {
        $this->beliefs = $beliefs;
        $this->topics = $topics;
        $this->edges = $edges;
    }

    public static function graphFromJson($jsonString) {
        $data = json_decode($jsonString, true);

        $beliefs = $data["beliefs"];
        $topics = $data["topics"];
        $edges = $data["edges"];

        return new self($beliefs, $topics, $edges);
    }

    public function addEdge($belief, $topic, $weight) {
        if (!in_array($belief, $this->beliefs) || !in_array($topic, $this->topics)) {
            throw new Exception("Belief or topic not found in graph.");
        }

        $this->edges[$belief][$topic] = $weight;
    }

    public function deleteEdge($belief, $topic) {
        if (!in_array($belief, $this->beliefs) || !in_array($topic, $this->topics)) {
            throw new Exception("Belief or topic not found in graph.");
        }

        unset($this->edges[$belief][$topic]);
    }

    public function updateEdge($belief, $topic, $newWeight) {
        if (!in_array($belief, $this->beliefs) || !in_array($topic, $this->topics)) {
            throw new Exception("Belief or topic not found in graph.");
        }

        $this->edges[$belief][$topic] = $newWeight;
    }

    public function getBeliefs() {
        return $this->beliefs;
    }

    public function getTopics() {
        return $this->topics;
    }

    public function getEdges() {
        return $this->edges;
    }

    public function getPositiveTopicPairs($belief) {
        $topicPairs = array();

        if(!empty($this->topics) && !empty($this->edges)){
			foreach ($this->topics as $topic1) {
				foreach ($this->topics as $topic2) {
					if (
						isset($this->edges[$belief][$topic1]) && 
						isset($this->edges[$belief][$topic2]) &&  
						$topic1 != $topic2 && $this->edges[$belief][$topic1] > 0 && $this->edges[$belief][$topic2] > 0) {						
						
						$keys = array_keys($this->edges[$belief]);
						$position_topic1 = array_search($topic1, $keys);
						$position_topic2 = array_search($topic2, $keys);
						
						if($position_topic1 == 0 && $position_topic2 == 1){
							$topicPairs[] = array($topic1, $topic2);							
						}
					}
				}
			}			
		}

        return $topicPairs;
    }

    public function getNegativeTopicPairs($belief) {
        $topicPairs = array();

		if(!empty($this->topics) && !empty($this->edges)){
			foreach ($this->topics as $topic1) {
				foreach ($this->topics as $topic2) {
					if (isset($this->edges[$belief][$topic1]) && 
						isset($this->edges[$belief][$topic2]) && 
						$topic1 != $topic2 && $this->edges[$belief][$topic1] < 0 && $this->edges[$belief][$topic2] < 0) {
						$topicPairs[] = array($topic1, $topic2);
					}
				}
			}
		}

        return $topicPairs;
    }
	
	public function findPathToGoalTopic($belief, $goalTopic) {
		$visited = array();
		$queue = array();

		// Find positive topic pairs and add their topics to the queue
		$positiveTopicPairs = $this->getPositiveTopicPairs($belief);
		foreach ($positiveTopicPairs as $pair) {
			if ($pair[0] == $goalTopic || $pair[1] == $goalTopic) {
				// Found goal topic as a direct neighbor
				return array($pair[0], $pair[1]);
			}
			if (!in_array($pair[0], $visited)) {
				$visited[] = $pair[0];
				$queue[] = array($pair[0]);
			}
			if (!in_array($pair[1], $visited)) {
				$visited[] = $pair[1];
				$queue[] = array($pair[1]);
			}
		}

		// BFS to find a path from positive topic pairs to goal topic
		while (count($queue) > 0) {
			$path = array_shift($queue);
			$lastNode = end($path);
			$neighbors = array();

			// Find neighbors of last node in the path
			foreach ($this->topics as $topic) {
				if ($topic != $lastNode && $this->edges[$belief][$lastNode] > 0 && $this->edges[$belief][$topic] > 0) {
					$neighbors[] = $topic;
				}
			}

			// Check if any of the neighbors is the goal topic
			foreach ($neighbors as $neighbor) {
				$newPath = $path;
				$newPath[] = $neighbor;
				if ($neighbor == $goalTopic) {
					return $newPath;
				}
				if (!in_array($neighbor, $visited)) {
					$visited[] = $neighbor;
					$queue[] = $newPath;
				}
			}
		}

		// Goal topic not found
		return null;
	}	
}

// Example usage:
/*
$jsonString = file_get_contents("example_graph.json");
$graph = DependencyGraph::graphFromJson($jsonString);

// Get topic pairs where belief1 is positively related to both topics:
$positivePairs = $graph->getPositiveTopicPairs("b0");

// Get topic pairs where belief1 is negatively related to both topics:
$negativePairs = $graph->getNegativeTopicPairs("b0");

//echo "Graph:<br>";
//print_r($graph);

echo "Positive pairs:<br>";
print_r($positivePairs);

echo "<br><br>";
echo "Negative pairs:<br>"; 
print_r($negativePairs); 
*/
?>