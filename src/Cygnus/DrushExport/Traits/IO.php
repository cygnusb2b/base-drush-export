<?php

namespace Cygnus\DrushExport\Traits;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Wrapper for core logic in Drupal imports
 */
trait IO {

  protected $indent = 0;

  /**
   * Indents all following output.
   *
   * @see     self::writeln()
   * @param   int     $n              Number of 'tabs' to indent by.
   */
  final protected function indent($num = 1) {
    $this->indent += $num;
  }

  /**
   * Outdents all following output.
   *
   * @see     self::writeln()
   * @param   int     $n              Number of 'tabs' to outdent by.
   */
  final protected function outdent($num = -1) {
    $this->indent += $num;
  }

  /**
   * Unified text output for extending classes. Writes lines in a standard way.
   *
   * @param   string  $text           The line of text to write to the screen.
   * @param   boolean $breakAfter     If a full line break should be added after the line.
   * @param   boolean $breakBefore    If a full line break should be added before the line.
   */
  final protected function writeln($text, $breakAfter = false, $breakBefore = false) {
    $indent = ($this->indent > 0) ? $this->indent : 0;
    $text = sprintf('%s%s', str_repeat(' ', 4 * $indent), $text);
    if (true === $breakAfter) $text = sprintf("%s\r\n", $text);
    if (true == $breakBefore) $text = sprintf("\r\n%s", $text);
    $this->output->writeln($text);
  }

  /**
   * Generic loop iterator.
   *
   * @param callable  $counter    A method that returns a count of items to process in this loop.
   * @param callable  $retriever  A method that returns items to process, accepting $limit and $skip.
   * @param callable  $modifier   A method that returns an item to be persisted
   * @param callable  $persister  A method that persists the requested changes
   * @param string    $label      Label for loop execution.
   * @param int       $limit      Number of items to process in each loop.
   * @param int       $skip       Number of items to skip initially.
   */
  final protected function loop(callable $counter, callable $retriever, callable $modifier, callable $persister, $label = null, $limit = 100, $skipStart = 0) {
    $count = $total = (int) $counter() - $skipStart;
    $modified = $index = 0;
    $steps = ceil($total / $limit);

    if (0 >= $total) {
        $this->writeln(sprintf('<error>Nothing to process for %s!</error>', $label));
        return;
    }

    $bar = $this->getProgressBar($total, $label);

    $this->writeln('');
    $bar->start();

    while ($count > 0) {
        $skip = $limit * $index + $skipStart;
        $items = $retriever($limit, $skip);
        $formatted = [];
        foreach ($items as $item) {
            $item = $modifier($item);
            if (null !== $item) {
                $formatted[] = $item;
            }
        }
        $persister($formatted);
        $modified += count($formatted);
        $index++;
        $count -= $limit;
        $bar->setMessage($modified, 'modified');
        $bar->setProgress($total - $count);
    }

    $bar->finish();
    $this->writeln('');
  }

  protected function getProgressBar($total = 0, $label = null) {
    $indent = $this->indent > 0 ? $this->indent : 0;
    $padding = str_repeat(' ', 4 * $indent);
    $format = $padding."\033[44;37m %title:-37s% \033[0m\n".$padding."%current%/%max% [%bar%] %percent:3s%%\n".$padding."%elapsed:-10s% 🏁  %remaining:-10s% 🤘 %modified% %memory:37s%";

    $bar = new ProgressBar($this->output, $total);
    ProgressBar::setPlaceholderFormatterDefinition('memory', function (ProgressBar $bar) {
        static $i = 0;
        $mem = 100000 * $i;
        $colors = $i++ ? '41;37' : '44;37';
        return "\033[".$colors.'m '. Helper::formatMemory($mem)." \033[0m";
    });

    $bar->setBarWidth(100);
    $bar->setBarCharacter('<fg=green>=</>');
    $bar->setEmptyBarCharacter('-');
    $bar->setProgressCharacter("\xF0\x9F\x8D\xBA");
    $bar->setFormat($format);
    $bar->setMessage(0, 'modified');

    $message = $label
      ? sprintf('Processing %s %s items...', $total, $label)
      : sprintf('Processing %s items...', $total);
    $bar->setMessage($message, 'title');

    return $bar;
  }

}