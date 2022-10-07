<?php declare(strict_types=1);

namespace Content;

use Content\Contract\BaseInterface;

/**
 *  Base class for easier navigation and memory managment of multibyte strings
 */
abstract class Base implements BaseInterface
{
    /**
     * To avoid creating at some point two variable which would hold whole texts
     * we are using pointer to indicate which version of content we are currently using
     * @var int $contentPointer
     */
    private int $contentPointer = -1;

    /**
     * Stores all existing versions of content. We keep created versions so we don't accidently
     * overload the memory when trying to replace content for few operations. It also holds
     * the size of the content.
     * Structure:
     * [
     *   [
     *     "content" => ['a','b','õ'],
     *     "size" => 3
     *   ]
     * ]
     * @var array
     */
    private array $contents = [];

    public function __construct(string $content)
    {
        $this->cutAndAddContent($content);
    }

    public function __toString(): string
	{
		return implode('', $this->contents[$this->contentPointer]['content']);
	}

    /**
     * Iterate over whole string and separate it into UTF-8 letter chunks
     * @param  string   $content
     * @param  int      $i
     * @param  array    $args
     * @param  callable $func
     */
    protected function iterate(string $content, int $i, array $args, callable $func)
    {
        $nextLetter = $i;
        $res = null;
        while (($letter = static::get($content, $i, $nextLetter)) !== false) {
            $res = $func($letter, $i, ...$args);
            $i = $nextLetter;
        }
        return $res;
    }

    protected function chunkString(string $content): array
    {
        return $this->iterate($content, 0, [[]],
            function (string $letter, int $i, array &$content)
            {
                $content[] = $letter;
                return $content;
            }
        ) ?? [];
    }

    /**
     * Cuts string into UTF-8 letters
     * @param string  $content Script
     * @param boolean $replace If to replace current version with new content
     */
    private function cutAndAddContent(string $content, bool $replace = false): void
    {
        if (!$replace) {
            $this->contentPointer++;
        }

        $this->contents[$this->contentPointer] = [];
        $this->contents[$this->contentPointer]['content'] = $this->chunkString($content);
        $this->contents[$this->contentPointer]['size'] = \sizeof($this->contents[$this->contentPointer]['content']);
    }

    /**
     * Clear contents and set contentPointer to default
     */
    private function clear(): void
    {
        $this->contents = [];
        $this->contentPointer = -1;
    }

    /**
     * Adds new text at the end of the content
     * @param  string  $content Text
     * @param  boolean $clear   If set to `true` will remove all old versions
     * @return self
     */
    public function addContent(string $content, bool $clear = false, bool $replace = false): self
    {
        if (!$replace && $clear) {
            $this->clear();
        }
        $this->cutAndAddContent($content, $replace);
        return $this;
    }

    /**
     * Adds already prepared content without need to iterate over it
     * @param  array   $content
     * @param  boolean $clear   Remove all versions?
     * @param  boolean $replace Replace current version?
     * @return self
     */
    public function addArrayContent(array $content, bool $clear = false, bool $replace = false): self
    {
        if (!$replace && $clear) {
            $this->clear();
        } elseif (!$replace) {
            $this->contentPointer++;
        }

        $this->contents[$this->contentPointer] = [
            'content' => $content,
            'size'    => \sizeof($content),
        ];
        return $this;
    }

    /**
     * Prepends text to the current version of content
     * @param  array $content Text in form of array
     * @return self
     */
    public function prependArrayContent(array $content): self
    {
        $content = array_merge($content, $this->contents[$this->contentPointer]['content']);
        $this->contents[$this->contentPointer] = [
            'content' => $content,
            'size'    => \sizeof($content),
        ];
        return $this;
    }

    /**
     * Apends text to the current version of content
     * @param  array $content Text in form of array
     * @return self
     */
    public function apendArrayContent(array $content): self
    {
        $content = array_merge($this->contents[$this->contentPointer]['content'], $content);
        $this->contents[$this->contentPointer] = [
            'content' => $content,
            'size'    => \sizeof($content),
        ];
        return $this;
    }

    /**
     * Returns current version of content array
     * @return array Content in form of an array
     */
    public function getContent(): array
    {
        return $this->contents[$this->contentPointer]['content'];
    }

    /**
     * Replaces current content with new one
     * @param  string $content Script
     * @return self
     */
    public function replaceContent(string $content): self
    {
        $this->cutAndAddContent($content, true);
        return $this;
    }

    /**
     * Returns current content size
     * @return int Size of current content
     */
    public function getLength(): int
    {
        return $this->contents[$this->contentPointer]['size'];
    }

    /**
     * Returns letter from current content
     * @param  int    $pos Index of letter
     * @return null|string Will return null if letter was not found
     */
    public function getLetter(int $pos): ?string
    {
        return $this->contents[$this->contentPointer]['content'][$pos] ?? null;
    }

    /**
     * Cuts content and joins all items to return string
     * @param  int      $start  From where start the cut
     * @param  null|int $length How long the string should be
     * @return string
     */
    public function subStr(int $start, ?int $length = null): string
    {
        $cut = array_slice($this->contents[$this->contentPointer]['content'], $start, $length);
        return implode('', $cut);
    }

    /**
     * Same as subStr but work on indexes not on length
     * @param  int    $start Where to start the cut
     * @param  int    $end   Index of where to end it
     * @return string
     */
    public function iSubStr(int $start, int $end): string
    {
        $cut = array_slice($this->contents[$this->contentPointer]['content'], $start, $end + 1 - $start);
        return implode('', $cut);
    }

    /**
     * Removes current content and decreases contentPointer
     * @return self
     */
    public function removeContent(): self
    {
        unset($this->contents[$this->contentPointer]);
        $this->contentPointer--;
        return $this;
    }

    /**
     * Similarly to subStr but it returns Content
     * @param  int     $start
     * @param  int     $length
     * @return BaseInterface
     */
    public function cutToContent(int $start, int $length): BaseInterface
    {
        $cut = array_slice($this->contents[$this->contentPointer]['content'], $start, $length);
        return (new static(''))->addArrayContent($cut, true);
    }

    /**
     * Similarly to iSubStr but it returns Content
     * @param  int     $start
     * @param  int     $end
     * @return BaseInterface
     */
    public function iCutToContent(int $start, int $end): BaseInterface
    {
        $cut = array_slice($this->contents[$this->contentPointer]['content'], $start, $end + 1 - $start);
        return (new static(''))->addArrayContent($cut, true);
    }

    /**
     * Similarly to subStr but it returns array
     * @param  int     $start
     * @param  int     $length
     * @return array
     */
    public function cutToArray(int $start, int $length): array
    {
        return array_slice($this->contents[$this->contentPointer]['content'], $start, $length);
    }

    /**
     * Similarly to iSubStr but it returns array
     * @param  int     $start
     * @param  int     $end
     * @return array
     */
    public function iCutToArray(int $start, int $end): array
    {
        return array_slice($this->contents[$this->contentPointer]['content'], $start, $end + 1 - $start);
    }

    public function trim($regex = "\s"): BaseInterface
    {
        $start = 0;
        $end   = $this->getLength();
        for ($i=0; $i < $this->getLength(); $i++) {
            $letter = $this->getLetter($i);
            if (preg_match('/' . $regex . '/', $letter) === 0) {
                $start = $i;
                break;
            }
        }

        for ($i=$this->getLength() - 1; $i >= 0; $i--) {
            $letter = $this->getLetter($i);
            if (preg_match('/' . $regex . '/', $letter) === 0) {
                $end = $i;
                break;
            }
        }
        return $this->iCutToContent($start, $end);
    }

    public function resize(): int
    {
        return $this->contents[$this->contentPointer]['size'] = \sizeof($this->contents[$this->contentPointer]['content']);
    }

    public function splice(int $start, ?int $length = 1, array|string $items = []): self
    {
        if (is_string($items)) {
            $items = $this->chunkString($items);
        }
        array_splice($this->contents[$this->contentPointer]['content'], $start, $length, $items);
        $this->resize();
        return $this;
    }

    public function iSplice(int $start, int $end, string|array $items = []): self
    {
        if (is_string($items)) {
            $items = $this->chunkString($items);
        }
        array_splice($this->contents[$this->contentPointer]['content'], $start, $end + 1 - $start, $items);
        $this->resize();
        return $this;
    }

    public function find(string $needle, int $start = 0): int
    {
        $len = \mb_strlen($needle);
        for ($i=$start; $i < $this->getLength(); $i++) {
            $hit = $this->subStr($i, $len);
            if ($hit == $needle) {
                return $i + $len - 1;
            }
        }
        return false;
    }

    public function reverse(): self
    {
        $this->replaceContent(implode('', array_reverse($this->getContent())));
        return $this;
    }

    public static function isWhitespace(string $string): bool
    {
        if (strlen($string) == 0) {
            return false;
        }

        // https://en.wikipedia.org/wiki/Whitespace_character
        $table = [
            // Unicode characters with property White_Space=yes
            "\u{0009}" => true, "\u{000A}" => true, "\u{000B}" => true, "\u{000C}" => true,
            "\u{000D}" => true, "\u{0020}" => true, "\u{0085}" => true, "\u{00A0}" => true,
            "\u{1680}" => true, "\u{2000}" => true, "\u{2001}" => true, "\u{2002}" => true,
            "\u{2003}" => true, "\u{2004}" => true, "\u{2005}" => true, "\u{2006}" => true,
            "\u{2007}" => true, "\u{2008}" => true, "\u{2009}" => true, "\u{200A}" => true,
            "\u{2028}" => true, "\u{2029}" => true, "\u{202F}" => true, "\u{205F}" => true,
            "\u{3000}" => true,
            // Related Unicode characters with property White_Space=no
            "\u{180E}" => true, "\u{200B}" => true, "\u{200C}" => true, "\u{200D}" => true,
            "\u{2060}" => true, "\u{FEFF}" => true,
        ];
        $nextLetter = $i = 0;

        // Iterate over the string and cut it into proper UTF-8 letters
        // We have to do this to have actual letters and not letters chunked into bytes
        // as they will be saved in multiple spaces, so spliting text by 1 with substr
        // will only return in trash characters if we encounter something that has length
        // bigger then 1 byte
        while (($letter = static::get($string, $i, $nextLetter)) !== false) {
            if (!isset($table[$letter])) {
                return false;
            }
            $i = $nextLetter;
        }

        return true;
    }
}
