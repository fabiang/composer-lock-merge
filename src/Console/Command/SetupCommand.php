<?php

/**
 * Copyright 2015 Fabian Grutschus. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of the copyright holders.
 *
 * @author    Fabian Grutschus <f.grutschus@lubyte.de>
 * @copyright 2015 Fabian Grutschus. All rights reserved.
 * @license   BSD-2-Clause
 * @link      http://github.com/fabiang/composer-lock-merge
 */

namespace Fabiang\ComposerLockMerge\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Fabiang\ComposerLockMerge\Console\Command\Exception\InvalidArgumentException;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SetupCommand extends Command
{

    protected function configure()
    {
        $this->setName('setup')
            ->setDescription('Setup your Git environment')
            ->addArgument(
                'git-config',
                InputArgument::OPTIONAL,
                'Path to Git config file',
                getenv('HOME') . '/.gitconfig'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configPath = rtrim($this->getConfigurationPath($input), '/');

        if (!is_file($configPath)) {
            if (is_dir($configPath)) {
                $configPath .= '/.gitconfig';
            } else {
                throw new InvalidArgumentException(sprintf('Config path "%s" does not exist', $configPath));
            }
        }

        $mergeTool = $this->getMergtoolConfig();

        $output->writeln(sprintf(
            "<info>Would write the following configuration to \"%s\":</info>\n%s",
            $configPath,
            $mergeTool
        ));

        $helper   = $this->getHelper('question');
        $question = new ConfirmationQuestion('<info>Continue with this action [y/n]?</info> ', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        copy($configPath, $configPath . '.bak');
        file_put_contents($configPath, $mergeTool, FILE_APPEND);
    }

    private function getConfigurationPath(InputInterface $input)
    {
        if ($input->hasArgument('git-config')) {
            return $input->getArgument('git-config');
        }

        return getenv('HOME') . '/.gitconfig';
    }

    private function getMergtoolConfig()
    {
        return sprintf("
[mergetool \"composer-lock-merge\"]
    cmd = %s merge \"\$PWD/\$BASE\" \"\$PWD/\$REMOTE\" \"\$PWD/\$LOCAL\" \"\$PWD/\$MERGED\"
    keepTemporaries = false

[alias]
    composer-lock-merge = mergetool --tool=composer-lock-merge --no-prompt composer.lock
",
            realpath($_SERVER['PHP_SELF'])
        );
    }
}
