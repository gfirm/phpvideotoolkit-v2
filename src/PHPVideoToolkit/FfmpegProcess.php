<?php
    
    /**
     * This file is part of the PHP Video Toolkit v2 package.
     *
     * @author Oliver Lillie (aka buggedcom) <publicmail@buggedcom.co.uk>
     * @license Dual licensed under MIT and GPLv2
     * @copyright Copyright (c) 2008-2014 Oliver Lillie <http://www.buggedcom.co.uk>
     * @package PHPVideoToolkit V2
     * @version 2.1.7-beta
     * @uses ffmpeg http://ffmpeg.sourceforge.net/
     */
     
    namespace PHPVideoToolkit;
     
    /**
     * undocumented class
     *
     * @access public
     * @author Oliver Lillie
     * @package default
     */
    class FfmpegProcess extends ProcessBuilder
    {
        protected $_exec;
        protected $_pre_input_commands;
        protected $_input;
        protected $_output_index;
        protected $_post_input_commands;
        protected $_output;
        protected $_post_output_commands;
        protected $_progress_handler;
        protected $_detect_error;
        protected $_combined;
        
        /**
         * Constructor
         *
         * @access public
         * @author Oliver Lillie
         * @param string $binary_path The path of ffmpeg or ffprobe or whatever program you will be
         *  executing the command on.
         * @param string $temp_directory The path of the temp directory.
         */
        public function __construct($program, Config $config=null)
        {
            parent::__construct($program, $config);
            
            $this->_pre_input_commands = array();
            $this->_input = array();
            $this->_output_index = 0;
            $this->_post_input_commands = array();
            $this->_output = array();
            $this->_post_output_commands = array();
            $this->_exec = null;
            $this->_progress_handler = null;
            $this->_combined = false;
        }

        /**
         * Sets the output index to a specific index.
         *
         * @access public
         * @author Oliver Lillie
         * @param integer $index The index integer to set the output index to.
         * @return self
         */
        public function setOutputIndex($index)
        {
            $this->_output_index = (int) $index;
            return $this;
        }
        
        /**
         * Sets the input at the given index. If -1 is used, the input is shifted
         * onto the begining of the input array.
         *
         * @access public
         * @param string $input
         * @param integer $index
         * @return self
         */
        public function setInputPath($input, $index=null)
        {
            if($index === null)
            {
                array_push($this->_input, $input);
            }
            else if($index === -1)
            {
                array_unshift($this->_input, $input);
            }
            else
            {
                $this->_input[$index] = $input;
            }
            return $this;
        }

        /**
         * Gets the input path at the given index.
         *
         * @access public
         * @param integer $index The index of which to return the input for.
         * @return string
         */
        public function getInputPath($index=0)
        {
            if(isset($this->_input[$index]) === true)
            {
                return $this->_input[$index];
            }
            
            throw new InvalidArgumentException('No input existed for given index `'.$index.'`');
        }
        
        /**
         * Gets ALL the input given to the process.
         *
         * @access public
         * @return array
         */
        public function getAllInput()
        {
            return $this->_input;
        }
        
        /**
         * Gets ALL the output given to the process.
         *
         * @access public
         * @return array
         */
        public function getAllOutput()
        {
            return $this->_output;
        }
        
        /**
         * Sets the output.
         *
         * @access public
         * @param string $output
         * @return self
         */
        public function setOutputPath($output)
        {
            $this->_output[$this->_output_index] = $output;
            return $this;
        }

        public function getOutputCount()
        {
            return count($this->_output);
        }

        /**
         * Gets the output.
         *
         * @access public
         * @param integer $index The index of the output to return. If left null defaults to the currently incremented
         *  index.
         * @return string
         */
        public function getOutputPath($index=null)
        {
            $index = $index === null ? $this->_output_index : $index;
            return isset($this->_output[$index]) === true ? $this->_output[$index] : null;
        }

        /**
         * Adds a command to be bundled into command line call to be 
         * added to the command line call before the input file is added.
         *
         * @access public
         * @param string $command
         * @param mixed $argument
         * @return self
         */
        public function addPreInputCommand($command, $argument=false, $allow_command_repetition=false)
        {
            $this->_add($this->_pre_input_commands, $command, $argument, $allow_command_repetition);
            return $this;
        }

        /**
         * Adds a command to be bundled into command line call to be 
         * added to the command line call after the input file is added.
         *
         * @access public
         * @param string $command
         * @param mixed $argument
         * @return self
         */
        public function addCommand($command, $argument=false, $allow_command_repetition=false)
        {
            if(isset($this->_post_input_commands[$this->_output_index]) === false)
            {
                $this->_post_input_commands[$this->_output_index] = array();
            }
            $this->_add($this->_post_input_commands[$this->_output_index], $command, $argument, $allow_command_repetition);
            return $this;
        }

        /**
         * Adds a command to be bundled into command line call to be 
         * added to the command line call after the ouput file is added.
         *
         * @access public
         * @param string $command
         * @param mixed $argument
         * @return self
         */
        public function addPostOutputCommand($command, $argument=false, $allow_command_repetition=false)
        {
            $this->_add($this->_post_output_commands, $command, $argument, $allow_command_repetition);
            return $this;
        }

        /**
         * Determines if the the command exits.
         *
         * @access public
         * @param string $command
         * @return mixed boolean if failure or value if exists.
         */
        public function hasPreInputCommand($command)
        {
            return isset($this->_pre_input_commands[$command]) === true ? ($this->_pre_input_commands[$command] === false ? true : $this->_pre_input_commands[$command]): false;
        }
        
        /**
         * Returns a pre input command.
         *
         * @access public
         * @param string $command
         * @return mixed boolean if failure or value if exists.
         */
        public function getPreInputCommand($command)
        {
            if($this->hasPreInputCommand($command) === false)
            {
                return false;
            }
            
            return $this->_pre_input_commands[$command];
        }
        
        /**
         * Determines if the the command exits.
         *
         * @access public
         * @param string $command
         * @param integer $index The index of the output to return. If left null defaults to the currently incremented
         *  index.
         * @return mixed boolean if failure or value if exists.
         */
        public function hasCommand($command, $index=null)
        {
            $index = $index === null ? $this->_output_index : $index;
            if(isset($this->_post_input_commands[$index]) === false)
            {
                return false;
            }
            return isset($this->_post_input_commands[$index][$command]) === true ? ($this->_post_input_commands[$index][$command] === false ? true : $this->_post_input_commands[$index][$command]): false;
        }
        
        /**
         * Returns a pre input command.
         *
         * @access public
         * @param string $command
         * @param integer $index The index of the output to return. If left null defaults to the currently incremented
         *  index.
         * @return mixed boolean if failure or value if exists.
         */
        public function getCommand($command, $index=null)
        {
            $index = $index === null ? $this->_output_index : $index;
            if($this->hasCommand($command, $index) === false)
            {
                return false;
            }
            
            return $this->_post_input_commands[$index][$command];
        }
        
        /**
         * Determines if the the command exits.
         *
         * @access public
         * @param string $command
         * @return mixed boolean if failure or value if exists.
         */
        public function hasPostOutputCommand($command)
        {
            return isset($this->_post_output_commands[$command]) === true ? ($this->_post_output_commands[$command] === false ? true : $this->_post_output_commands[$command]): false;
        }
        
        /**
         * Returns a post output command.
         *
         * @access public
         * @param string $command
         * @return mixed boolean if failure or value if exists.
         */
        public function getPostOutputCommand($command)
        {
            if($this->hasPostOutputCommand($command) === false)
            {
                return false;
            }
            
            return $this->_post_output_commands[$command];
        }
        
        /**
         * Combines the commands stored into a string
         *
         * @access protected
         * @return void
         */
        protected function _combineCommands()
        {
            if($this->_combined === true)
            {
                return;
            }
            $this->_combined = true;

            $args = $this->_arguments;
            $this->_arguments = array();
            
//          add the pre input commands
            if(empty($this->_pre_input_commands) === false)
            {
                $this->addCommands($this->_pre_input_commands);
            }
            
//          add in the input
            if(empty($this->_input) === false)
            {
                foreach ($this->_input as $input)
                {
                    $this->add('-i')
                         ->add($input);
                }
            }

//          build the multiple post input  and output path commands
            for($i=0; $i<=$this->_output_index; $i++)
            {
//              build the post input commands
                if(isset($this->_post_input_commands[$i]) === true && empty($this->_post_input_commands[$i]) === false)
                {
                    $this->addCommands($this->_post_input_commands[$i]);
                }
                if(empty($args) === false)
                {
                    $this->_arguments = array_merge($this->_arguments, $args);
                }
            
//              add in the output
                if(isset($this->_output[$i]) === true && empty($this->_output[$i]) === false)
                {
                    $this->add($this->_output[$i]);
                }
            }
            
//          build the post output commands
            if(empty($this->_post_output_commands) === false)
            {
                $this->addCommands($this->_post_output_commands);
            }
        }
        
        /**
         * Returns the command string to be executed.
         *
         * @access public
         * @author Oliver Lillie
         * @return string
         */
        public function getCommandString()
        {
            $this->_combineCommands();
            return parent::getCommandString();
        }
        
        /**
         * Get the ExecBuffer object by combining the commands the creating in the buffer.
         *
         * @access protected
         * @author Oliver Lillie
         * @return ExecBuffer
         */
        protected function _getExecBuffer()
        {
            $this->_combineCommands();
            return parent::getExecBuffer();
        }
        
        /**
         * Get the initialised ExecBuffer object.
         *
         * @access public
         * @author Oliver Lillie
         * @return ExecBuffer
         */
        public function &getExecBuffer()
        {
            if(empty($this->_exec) === true)
            {
                $this->_exec = $this->_getExecBuffer();
            }
            return $this->_exec;
        }
        
        /**
         * Execute the buffer command.
         *
         * @access public
         * @author Oliver Lillie
         * @return self
         */
        public function execute()
        {
            $this->getExecBuffer()
                 ->setBlocking(true)
                 ->execute();
            
            return $this;
        }
        
        /**
         * Protected private function for calling functions from the ExecBuffer.
         *
         * @access protected
         * @author Oliver Lillie
         * @param string $function 
         * @param array $arguments 
         * @return mixed
         */
        protected function _callExecBufferFunction($function, $arguments=array())
        {
//          if no exec has been created then it has not completed.
            if(empty($this->_exec) === true)
            {
                return false;
            }
            
            if(is_callable(array($this->_exec, $function)) === false)
            {
                throw new FfmpegProcessException('This function is not callable within ExecBuffer.', $this->_exec, $this);
            }
            
            return call_user_func_array(array($this->_exec, $function), $arguments);
        }
        
        /**
         * Returns any "[xxx @ xxxxx] message" messages set in the buffer by FFmpeg.
         *
         * @access public
         * @author Oliver Lillie
         * @return array
         */
        public function getMessages()
        {
            $messages = array();
            $buffer = $this->getBuffer();
            if(empty($buffer) === false)
            {
                // 0x7f9db9065a00
                if(preg_match_all('/\[([a-zA-Z0-9]+) @ (0x[a-z0-9]+)\] (.*)/', $buffer, $matches) > 0)
                {
                    foreach ($matches[1] as $key=>$match)
                    {
                        if(isset($messages[$match]) === false)
                        {
                            $messages[$match] = array();
                        }
                        if(isset($messages[$match][$matches[2][$key]]) === false)
                        {
                            $messages[$match][$matches[2][$key]] = array();
                        }
                        array_push($messages[$match][$matches[2][$key]], $matches[3][$key]);
                    }
                }
            }
            return $messages;
        }
        
        /**
         * Returns the current (or if called after isCompleted() returns true, the completed)
         * run time of the exec function.
         *
         * @access public
         * @author Oliver Lillie
         * @return mixed
         */
        public function getRunTime()
        {
            return $this->_callExecBufferFunction('getRunTime');
        }
        
        /**
         * Returns the buffers command or executed command.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getExecutedCommand
         * @param boolean $raw If true then the raw command is returned from the buffer, otherwise
         *  the original command is returned.
         * @return mixed
         */
        public function getExecutedCommand($raw=false)
        {
            return $this->_callExecBufferFunction($raw === false ? 'getCommand' : 'getExecutedCommand');
        }
        
        /**
         * Returns the filtered buffer output of ExecBuffer.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getBuffer
         * @return mixed
         */
        public function getBuffer($raw=false)
        {
            return $this->_callExecBufferFunction($raw === false ? 'getBuffer' : 'getRawBuffer');
        }
        
        /**
         * Returns the last line from the buffer output of ExecBuffer.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getLastLine
         * @return mixed
         */
        public function getLastLine()
        {
            return $this->_callExecBufferFunction('getLastLine');
        }
        
        /**
         * Returns the last split from the buffer output of ExecBuffer.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getLastSplit
         * @return mixed
         */
        public function getLastSplit()
        {
            return $this->_callExecBufferFunction('getLastSplit');
        }
        
        /**
         * Returns the error code encountered by the ExecBuffer.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getErrorCode
         * @return mixed
         */
        public function getErrorCode()
        {
            return $this->_callExecBufferFunction('getErrorCode');
        }
        
        /**
         * Returns a boolean value determining if the process has encountered an error.
         * Typically if this returns true, it also means the process has completed.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::hasError
         * @param boolean $delete_output_on_error If true and an error has been encountered
         *  and the output has been set and the output exists, then the output is deleted.
         * @return boolean
         */
        public function hasError($delete_output_on_error=true)
        {
            $has_error = $this->_callExecBufferFunction('hasError');
            
//          if we have an error and we want to delete any output on the error
            if($delete_output_on_error === true && $has_error === true)
            {
                foreach ($this->_output as $output)
                {
                    if(empty($output) === false && is_file($output) === true)
                    {
                        @unlink($output);
                    }
                }
            }
            
            return $has_error;
        }
        
        /**
         * Returns a boolean value determining if the process has completed.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::isCompleted
         * @return boolean
         */
        public function isCompleted()
        {
            return $this->_callExecBufferFunction('isCompleted');
        }
        
        /**
         * Returns the file name of the exec buffer output.
         *
         * @access public
         * @author Oliver Lillie
         * @see ExecBuffer::getBufferOutput
         * @return string
         */
        public function getBufferOutput()
        {
            return $this->_callExecBufferFunction('getBufferOutput');
        }
        
        /**
         * Returns a string value of a portable identifier used in conjunction with ProgressHandlerPortable.
         * WARNING. If this function is called it automatically disables the garbage collection of the ExceBuffer.
         * WARNING. This function should not be called directly. Please use Media::getPortableId instead.
         *
         * @access public
         * @author Oliver Lillie
         * @return string
         */
        public function getPortableId()
        {
            if($this->_callExecBufferFunction('getBlocking') === true)
            {
                throw new Exception('It is not possible to get a portable id as the exec process has been made blocking. To get a portable id make the process unblocking or call getPortableId() before the save occurs.');
            }

            $this->_exec->setGarbageCollection(false);
            
            $output = $this->getBufferOutput();
            return substr($output, strrpos($output, 'phpvideotoolkit_')+16).'.'.$this->_callExecBufferFunction('getBoundary').'.'.time();
        }
    }
