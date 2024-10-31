# Goal-Hiding Dialogues (GHD) Framework

A PHP implementation of the formal framework for goal-hiding information-seeking dialogues. This code is used in the empirical evaluation presented in the paper *Goal-hiding Information-seeking Dialogues: A Formal Framework* (under review).

## Installation
Clone the repository and ensure PHP is installed in your environment.

```bash
git clone https://github.com/AndreasbCS/goal-hiding-dialogues-framework.git
cd goal-hiding-dialogues-framework
```

## Files

- `DialogueManager.php`: Tracks and manages dialogue states.
- `QbafManager.php`: Implementation of Quantitative Bipolar Argumentation Framework (QBAF). Manages multiple QBAF instances, calculating argument strength changes over time.
- `DependencyGraph.php`: Manages the belief-topic dependency graph.
- `evaluation1-dependencyGraph.json`: Specifies a particular dependency graph with belief-topic realtions.
- `evaluation1-dialogueScript.json`: Specifies a particular dialogue script with topics and belief mappings for dialogue sequences.
- `evaluation1-QBAF.json`: Specifies a particular QBAF structure.
- `DialogueProcess.php`: Initialization file for the experiment.

# Initialization 

`DialogueProcess.php` initializes the dialogue reasoning process, using predefined parameters and JSON-encoded knowledge bases. It processes an input dialogue history to track changes in beliefs and topics and outputs QBAFs for each dialogue state.

### Parameters to Set

- **Discount Factor (`$discount`)**: Determines the decay rate of influence of topic strength. Default:
  ```php
  $discount = 0.2;
  ```
- **Sensitivity Interval (`$sensitivityInterval`)**: Sets a range for topic strength within which topics are considered valid to open. Default:
  ```php
  $sensitivityInterval = array(
      'lowerBound' => 0.5, 
      'upperBound' => 0.7
  );
  ```

### Input

- **Dialogue History (`$input`)**: A string representing the dialogue sequence, where topics (e.g., `t0`, `t1`) and beliefs (e.g., `b1`, `b4`) alternate as the dialogue progresses. An example:
  ```php
  $input = "t0, b1, t1, b4, t2, b8, t3, b12, t4";
  ```
  This input is processed to create an array of dialogue moves, where:
    - Each **topic** (e.g., `t0`) corresponds to an `open_topic` move by the `seeker`.
    - Each **belief** (e.g., `b1`) corresponds to an `assert_belief` move by the `respondent`.

### Breakdown of DialogueProcess.php

1. **Load Knowledge Bases**: Load `evaluation1-dependencyGraph.json`, `evaluation1-QBAF.json`, and `evaluation1-dialogueScript.json`.
2. **Convert Dialogue Input**: Parse the input string to create a sequence of dialogue moves.
3. **Process Dialogue**: After each move, the `DialogueManager` updates the dialogue state:
   - **Open Topic (`tX`)**: The `seeker` initiates a new topic.
   - **Assert Belief (`bX`)**: The `respondent` asserts beliefs.
4. **QBAF States**: In each dialogue state, the `QbafManager` adds a new QBAF state, reflecting changes in strengths of topics over dialogue state transitions.
5. **Log Output**: The dialogue log presents the QBAFs for each dialogue state, highlighting strengths of each topic changes and newly activated beliefs, relations and topics.

### Output

- **QBAFs in Each Dialogue State**: After processing the dialogue history, each QBAF state is output in sequence, showing how topic strengths change across dialogue states.
- **Next Suggested Topic**: Based on the goal topic and current dialogue state, the framework suggests the next topic to open.

### Example Initialization

```php
// Load JSON files
$dependencyGraph_json = file_get_contents("evaluation1-dependencyGraph.json");
$qbaf_json = file_get_contents("evaluation1-QBAF.json");

// Initialize DependencyGraph, QBAF, and Managers
$dependencyGraph = DependencyGraph::graphFromJson($dependencyGraph_json);
$qbaf = Qbaf::qbafFromJson($qbaf_json);
$dialogueLog = new DialogueLog();
$qbafManager = new QbafManager([], $discount, $dialogueLog);
$qbafManager->addQbaf($qbaf);

$dialogueManager = new DialogueManager(
    $topics = $dependencyGraph->getTopics(),
    $beliefs = $dependencyGraph->getBeliefs(),
    $qbafManager,
    $dependencyGraph,
    $discount,
    $sensitivityInterval,
    $dialogueLog
);
```

### Authors

* Andreas Brännström {andreasb@cs.umu.se} [Homepage](https://people.cs.umu.se/andreasb/)
* Virginia Dignum {virginia@cs.umu.se} [Homepage](https://www.umu.se/en/staff/virginia-dignum/)
* Juan Carlos Nieves {jcnieves@cs.umu.se} [Homepage](https://www.umu.se/en/staff/juan-carlos-nieves/)

Department of Computing Science  
Umeå university  
SE-901 87, Umeå, Sweden  
