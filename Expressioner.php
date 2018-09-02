<?php


interface Expression {};

interface Expressable {
    public function _and(array $operands);
    public function _or(array $operands);
    public function _field($field);
    public function _not($op1);
    public function _notEqual($op1, $op2);
    public function _exactly($op1, $op2);
    public function _value($value);
    public function _gte($op1, $op2);
    public function _gt($op1, $op2);
    public function _lte($op1, $op2);
    public function _lt($op1, $op2);
  }
abstract class BaseExpression implements Expression {
     protected $operands = [];

     public function __construct($string)
     {
          $this->operands[] = $string;
     }

    public function getOperands() {
          return $this->operands;
      }
}
class NaryExpr extends BaseExpression
{
    public function __construct(array $expressions)
    {
        foreach($expressions as $expr) {
            $this->operands[] = $expr;
        }
    }
}
class TernaryExpr extends BaseExpression
{
    public function __construct(Expression $a, Expression $b, Expression $c)
    {
        $this->operands[] = $a;
        $this->operands[] = $b;
        $this->operands[] = $c;
    }
}
class BinaryExpr extends BaseExpression
{
    public function __construct(Expression $a, Expression $b)
    {
        $this->operands[] = $a;
        $this->operands[] = $b;
    }
}
class UnaryExpr extends BaseExpression
{
    public function __construct($a)
    {
        $this->operands[] = $a;
    }
}


class andExpr extends NaryExpr {}
class orExpr extends NaryExpr {}
class notExpr extends UnaryExpr {}
class equalExpr extends BinaryExpr {}
class notEqualExpr extends BinaryExpr {}
class exactlyExpr extends BinaryExpr {}
class notExactlyExpr extends BinaryExpr {}
class betweenExpr extends TernaryExpr {}
class notBetweenExpr extends TernaryExpr {}
class inExpr extends BinaryExpr {}
class notInExpr extends BinaryExpr {}
class gtExpr extends BinaryExpr {}
class ltExpr extends BinaryExpr {}
class gteExpr extends BinaryExpr {}
class lteExpr extends BinaryExpr {}
class likeExpr extends BinaryExpr {}
class notLikeExpr extends BinaryExpr {}
class valueExpr extends BaseExpression {}
class fieldExpr extends BaseExpression {}
class likelyExpr extends UnaryExpr implements Expression{}
class notLikelyExpr extends UnaryExpr implements Expression{}
class ifelseExpr extends TernaryExpr implements Expression{}
class Operand
{
  public static function _and(...$expressions)
  {
      $b = new andExpr($expressions);
      return $b;
  }
  public static function _or(...$expressions)
  {
      $b = new orExpr($expressions);
      return $b;
  }
  public static function _not(Expression $a)
  {
      return new notExpr($a);
  }
  public static function _equal($a, $b)
  {
      return new equalExpr($a, $b);
  }
  public static function _notEqual($a, $b)
  {
      return new notEqualExpr($a, $b);
  }
  public static function _exactly($a, $b)
  {
      return new exactlyExpr($a, $b);
  }
  public static function _notExactly($a, $b)
  {
      return new notExactlyExpr($a, $b);
  }
  public static function _between($a, $b, $c)
  {
      return new betweenExpr($a, $b, $c);
  }
  public static function _notBetween($a, $b, $c)
  {
      return new notBetweenExpr($a, $b, $c);
  }
  public static function _in($a, $b)
  {
      return new inExpr($a, $b);
  }
  public static function _notIn($a, $b)
  {
      return new notInExpr($a, $b);
  }
  public static function _gt($a, $b)
  {
      return new gtExpr($a, $b);
  }
  public static function _lt($a, $b)
  {
      return new ltExpr($a, $b);
  }
  public static function _gte($a, $b)
  {
      return new gteExpr($a, $b);
  }
  public static function _lte($a, $b)
  {
      return new lteExpr($a, $b);
  }
  public static function _like($a, $b)
  {
      return new likeExpr($a, $b);
  }
  public static function _notLike($a, $b)
  {
      return new notLikeExpr($a, $b);
  }
  public static function _likely($a)
  {
      return new likelyExpr($a);
  }
  public static function _notLikely($a)
  {
      return new notLikelyExpr($a);
  }
  public static function _ifelse($a,$b,$c)
  {
      return new ifelseExpr($a,$b,$c);
  }
  public static function _field($a)
  {
      return new fieldExpr($a);
  }
  public static function _value($a)
  {
      return new valueExpr($a);
  }
}

trait OperandExtractor
{
    public function extractBaseOperand(BaseExpression $e)
    {
        $c = $e->getOperands();
        return $c[0];
    }

    public function extractNaryOperands(NaryExpr $e) {
      $c = $e->getOperands();
      $l = [];
      foreach($c as $i) {
          $l[] = $this->evaluate($i);
      }
      return $l;
    }

    public function extractUnaryOperand(UnaryExpr $e) {
      $c = $e->getOperands();
      $a = $this->evaluate($c[0]);
      return $a;
    }

    public function extractBinaryOperands(BinaryExpr $e) {
      $c = $e->getOperands();
      $a = $this->evaluate($c[0]);
      $b = $this->evaluate($c[1]);
      return [$a, $b];
    }

    public function extractTernaryOperands(TernaryExpr $e) {
      $c = $e->getOperands();
      $a = $this->evaluate($c[0]);
      $b = $this->evaluate($c[1]);
      $c = $this->evaluate($c[2]);
      return [$a, $b, $c];
    }
  }


class ExpressionEngine
{

    private $engine;
    use OperandExtractor;

    const baseExpressions = [
        fieldExpr::class,
        valueExpr::class
    ];

    const naryExpressions = [
        andExpr::class,
        orExpr::class
    ];

    const unaryExpressions = [
        notExpr::class
    ];

    const binaryExpressions = [
        equalExpr::class,
        notEqualExpr::class,
        exactlyExpr::class,
        gteExpr::class,
        gtExpr::class,
        lteExpr::class,
        ltExpr::class
    ];

    const ternaryExpressions = [];


    public function __construct(Expressable $engine)
    {
        $this->engine = $engine;
    }

    public function evaluate(Expression $e)
    {
        if (!is_object($e)) {
            throw new \InvalidArgumentException();
        }

        $classExpr = get_class($e);
        $method = "_".explode('Expr', $classExpr)[0];

        if (in_array($classExpr, $this::baseExpressions)) {
            $base = $this->extractBaseOperand($e);
            return $this->engine->{$method}( $base );
        }

        if (in_array($classExpr, $this::naryExpressions)) {
            $operands = $this->extractNaryOperands($e);
            return $this->engine->{$method}( $operands );
        }

        if (in_array($classExpr, $this::unaryExpressions)) {
            $op = $this->extractBinaryOperands($e);
            return $this->engine->{$method}( $op );
        }

        if (in_array($classExpr, $this::binaryExpressions)) {
            list ($op1, $op2) = $this->extractBinaryOperands($e);
            return $this->engine->{$method}( $op1, $op2 );
        }

        if (in_array($classExpr, $this::ternaryExpressions)) {
            list ($op1, $op2, $op3) = $this->extractTernaryOperands($e);
            return $this->engine->{$method} ( $op1, $op2, $op3 );
        }

        throw new Exception('not implemented');
    }
}


class Expressioner {
    private $engine;

    public function __construct(Expressable $search, ExpressionEngine $generator = null) {
        if (is_null($generator)) {
            $this->engine = new ExpressionEngine($search);
        }    else {
            $this->engine  = new $generator($search);
        }
    }

    public function generate(Expression $expression) {
        return $this->engine->evaluate( $expression ) ;
    }
}


class SqlExpression implements Expressable {
    /**
     * @param array $operands
     * @return string
     */
    public function _and(array $operands)
    {
        $l = implode(' AND ', $operands);
        return "($l)";
    }

    /**
     * @param array $operands
     * @return string
     */
    public function _or(array $operands)
    {
        $l = implode(' OR ', $operands);
        return "($l)";
    }

    /**
     * @param $field
     * @return string
     */
    public function _field($field)
    {
        return "`$field`";
    }

    /**
     * @param $value
     * @return string
     */
    public function _value($value)
    {
        return "'$value'";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _equal($op1, $op2)
    {
        return "($op1 = $op2)";
    }

    public function _not($op1)
    {
        return "(NOT $op1)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _notEqual($op1, $op2)
    {
        return "($op1 <> $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _exactly($op1, $op2)
    {
        return "(($op1) IS NOT NULL AND ($op1 = $op2))";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _gte($op1, $op2)
    {
        return "($op1 >= $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _gt($op1, $op2)
    {
        return "($op1 > $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _lte($op1, $op2)
    {
        return "($op1 <= $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _lt($op1, $op2)
    {
        return "($op1 $op2)";
    }
}

class PHPExpression implements Expressable {

    /**
     * @param array $operands
     * @return string
     */
    public function _and(array $operands) {
        return implode(' && ', $operands);
    }

    /**
     * @param array $operands
     * @return string
     */
    public function _or(array $operands) {
        $l = implode(' || ', $operands);
        return "($l)";
    }

    /**
     * @param $field
     * @return string
     */
    public function _field($field) {
        return "\$row['$field']";
    }

    /**
     * @param $op1
     */
    public function _not($op1)
    {
        return "(!$op1)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _notEqual($op1, $op2) {
        return "($op1 != $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _exactly($op1, $op2) {
        return "($op1 == $op2)";
    }

    /**
     * @param $value
     */
    public function _value($value) {
        return "'$value'";
    }

    /**
     * @param $op1
     * @param $op2
     */
    public function _gte($op1, $op2) {
        return "($op1 >= $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _gt($op1, $op2) {
        return "($op1 > $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     */
    public function _lte($op1, $op2) {
        return "($op1 <= $op2)";
    }

    /**
     * @param $op1
     * @param $op2
     * @return string
     */
    public function _lt($op1, $op2) {
        return "($op1 < $op2)";

    }
}


$expressionGenerator = new Expressioner(new SqlExpression());
$query =  Operand::_or(
            Operand::_equal(Operand::_field('hello'), Operand::_value('value')),
            Operand::_notEqual(Operand::_field('age'), Operand::_value(55)),
            Operand::_equal(Operand::_field('hair'), Operand::_value('black'))
          );

$results = $expressionGenerator->generate($query);
print_r($results)

?>
