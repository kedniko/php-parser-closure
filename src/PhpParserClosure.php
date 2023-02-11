<?php

namespace Kedniko\PhpParserClosure;

use PhpParser\ParserFactory;

class PhpParserClosure
{
    private \ReflectionFunction $reflection;
    private \PhpParser\Parser $parser;
    private \Closure $closure;
    private \PhpParser\Node\Expr\Closure|null $node;
    private string|null $namespace;
    private array $uses;
    private array $ast;
    private bool $rememberFileContent;
    private string|null $fileContent;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->rememberFileContent = false;
        $this->fileContent = null;
        $this->node = null;
    }

    public function parse(\Closure $closure)
    {
        $this->closure = $closure;
        $rf = new \ReflectionFunction($this->closure);
        $this->reflection = $rf;

        $filename = $rf->getFileName();
        $start_line = $rf->getStartLine();
        $end_line = $rf->getEndLine();
        $source = file_get_contents($filename);
        if ($this->rememberFileContent) {
            $this->fileContent = $source;
        }

        $traverser = new \PhpParser\NodeTraverser();

        $bag = [
            'resultNode' => null,
            'namespace'  => null,
            'uses'       => [],
        ];
        $traverser->addVisitor($this->getClosureVisitor($bag, $start_line, $end_line));
        $this->ast = $this->parser->parse($source);
        $traverser->traverse($this->ast);
        $this->node = $bag['resultNode'];
        $this->namespace = $bag['namespace'];
        $this->uses = $bag['uses'];
        return $this->ast;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getUses()
    {
        return $this->uses;
    }

    public function getClosure()
    {
        return $this->closure;
    }

    public function rememberFileContent()
    {
        $this->rememberFileContent = true;
        return $this;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    public function getFileContent()
    {
        return $this->fileContent;
    }

    public function getCode()
    {
        $prettyPrinter = new \PhpParser\PrettyPrinter\Standard();
        return $prettyPrinter->prettyPrint([$this->node]);
    }

    public function getNode(): \PhpParser\Node|null
    {
        return $this->node;
    }

    private function getClosureVisitor(&$bag, $start_line, $end_line)
    {
        return new class ($bag, $start_line, $end_line) extends \PhpParser\NodeVisitorAbstract {
            public function __construct(private &$bag, private int $closure_start_line, private int $closure_end_line)
            {
            }

            public function enterNode(\PhpParser\Node $node)
            {
                $attributes = $node->getAttributes();
                $startLine = $attributes['startLine'];
                $endLine = $attributes['endLine'];

                if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                    $this->bag['namespace'] = $node->name->toString();
                    $uses = [];
                    foreach ($node->stmts as $stmt) {
                        if ($stmt instanceof \PhpParser\Node\Stmt\Use_) {
                            foreach ($stmt->uses as $use) {
                                $alias = $use->getAlias()->toString();
                                $uses[$alias] = $use->name->toString();
                            }
                        }
                    }
                    $this->bag['uses'] = $uses;
                }

                if (!$this->isInInterval($startLine, $endLine)) {
                    return \PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }
                if ($node instanceof \PhpParser\Node\Expr\Closure) {
                    if ($startLine === $this->closure_start_line && $endLine === $this->closure_end_line) {
                        $this->bag['resultNode'] = $node;
                        return \PhpParser\NodeTraverser::STOP_TRAVERSAL;
                    }
                }
            }

            private function isInInterval($startLine, $endLine)
            {
                return $startLine <= $this->closure_start_line && $endLine >= $this->closure_end_line;
            }
        };
    }
}
