<?php
/**
 * Copyright 2011 Bas de Nooijer. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this listof conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are
 * those of the authors and should not be interpreted as representing official
 * policies, either expressed or implied, of the copyright holder.
 *
 * @copyright Copyright 2011 Bas de Nooijer <solarium@raspberry.nl>
 * @license http://github.com/basdenooijer/solarium/raw/master/COPYING
 * @link http://www.solarium-project.org/
 */

/**
 * @namespace
 */
namespace Solarium\QueryType\Select\Result\Spellcheck;

/**
 * Select component spellcheck collation result
 */
class Collation implements \IteratorAggregate, \Countable
{
    /**
     * Query
     *
     * @var string
     */
    protected $query;

    /**
     * Hit count
     *
     * @var int
     */
    protected $hits;

    /**
     * Corrections
     *
     * @var array
     */
    protected $corrections;

    /**
     * Constructor
     *
     * @param string   $query
     * @param int|null $hits
     * @param array    $corrections
     */
    public function __construct($query, $hits, $corrections)
    {
        $this->query = $query;
        $this->hits = $hits;
        $this->corrections = $corrections;
    }

    /**
     * Get query string
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get hit count
     *
     * Only available if ExtendedResults was enabled in your query
     *
     * @return int|null
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Get all corrrections
     *
     * Only available if ExtendedResults was enabled in your query
     *
     * @return array
     */
    public function getCorrections()
    {
        return $this->corrections;
    }

    /**
     * IteratorAggregate implementation
     *
     * Only available if ExtendedResults was enabled in your query
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->corrections);
    }

    /**
     * Countable implementation
     *
     * Only available if ExtendedResults was enabled in your query
     *
     * @return int
     */
    public function count()
    {
        return count($this->corrections);
    }
}
