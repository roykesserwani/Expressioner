<?php

$expressionGenerator = new Expressioner(new SqlExpression());
$query = Operand::_equal(Operand::_field('hello'), Operand::_value('value')),
$results = $expressionGenerator->generate($query);



?>
