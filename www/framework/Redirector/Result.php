<?php

namespace Depage\Redirector {

    /**
     * brief Result
     * Class Result
     */
    class Result
    {
        /**
         * @brief path
         **/
        protected $path = "";

        /**
         * @brief isFallback
         **/
        protected $isFallback = false;

        // {{{ constructor
        /**
         * @brief construct
         *
         * @param mixed $
         * @return void
         **/
        public function __construct($path, $isFallback = false)
        {
            $this->path = $path;
            $this->isFallback = $isFallback;
        }
        // }}}
        // {{{ __toString()
        /**
         * @brief __toString
         *
         * @return void
         **/
        public function __toString()
        {
            return $this->path;

        }
        // }}}
        // {{{ isFallback()
        /**
         * @brief isFallback
         *
         * @param mixed
         * @return void
         **/
        public function isFallback()
        {
            return $this->isFallback;
        }
        // }}}
    }

}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
