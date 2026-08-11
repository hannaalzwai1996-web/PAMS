<?php

use App\Domain\LearningOutcome\Services\Support\LexicalCorrelationScorer;

beforeEach(function () {
    $this->scorer = new LexicalCorrelationScorer;
});

test('identical statements score 1.0 and suggest High', function () {
    $statement = 'Graduates will apply professional engineering knowledge and analysis.';

    expect($this->scorer->score($statement, $statement))->toBe(1.0);
    expect($this->scorer->suggestLevel($statement, $statement))->toBe(3);
});

test('completely unrelated statements score 0.0 and suggest no correlation', function () {
    $a = 'Graduates will apply professional engineering analysis techniques.';
    $b = 'Students demonstrate ethical workplace communication behavior.';

    expect($this->scorer->score($a, $b))->toBe(0.0);
    expect($this->scorer->suggestLevel($a, $b))->toBeNull();
});

test('partial vocabulary overlap produces a score between 0 and 1', function () {
    $a = 'Graduates will apply engineering analysis techniques to design problems.';
    $b = 'Students perform engineering analysis when solving design problems.';

    $score = $this->scorer->score($a, $b);

    expect($score)->toBeGreaterThan(0.0)->toBeLessThanOrEqual(1.0);
    expect($this->scorer->suggestLevel($a, $b))->toBeIn([1, 2, 3]);
});

test('stopwords and short words do not count toward overlap', function () {
    $a = 'It is on the by an at';
    $b = 'to of for as this that will';

    expect($this->scorer->score($a, $b))->toBe(0.0);
});

test('score is symmetric', function () {
    $a = 'Graduates will demonstrate effective written and oral communication.';
    $b = 'Students communicate technical results effectively in written reports.';

    expect($this->scorer->score($a, $b))->toEqual($this->scorer->score($b, $a));
});

test('an empty statement always scores 0.0', function () {
    expect($this->scorer->score('', 'Graduates will apply knowledge.'))->toBe(0.0);
    expect($this->scorer->score('the a an', 'Graduates will apply knowledge.'))->toBe(0.0);
});
