def REPO_NAME = env.JOB_NAME.split("/")[0]

pipeline {
  options {
    skipDefaultCheckout()
    disableConcurrentBuilds()
  }
  agent {
    kubernetes {
      label 'mautic-hosted-build'
      inheritFrom 'with-mysql'
      containerTemplate {
        name 'hosted-tester'
        image 'us.gcr.io/mautic-ma/mautic_tester:master'
        ttyEnabled true
        command 'cat'
      }
    }
  }
  stages {
    stage('Download and combine') {
      steps {
        container('hosted-tester') {
          checkout changelog: false, poll: false, scm: [$class: 'GitSCM', branches: [[name: 'beta']], doGenerateSubmoduleConfigurations: false, extensions: [[$class: 'SubmoduleOption', disableSubmodules: false, parentCredentials: true, recursiveSubmodules: true]], submoduleCfg: [], userRemoteConfigs: [[credentialsId: '1a066462-6d24-4247-bef6-1da084c8f484', url: 'git@github.com:mautic-inc/mautic-cloud.git']]]
          sh('rm -r plugins/IntegrationsBundle || true; mkdir -p plugins/IntegrationsBundle && chmod 777 plugins/IntegrationsBundle')
          dir('plugins/IntegrationsBundle') {
            checkout scm
          }
        }
      }
    }
    stage('Build') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              composer install --ansi
            """
            dir('plugins/IntegrationsBundle') {
              sh("composer install --ansi")
            }
          }
        }
      }
    }
    stage('Test') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            sh """
              mysql -h 127.0.0.1 -e 'CREATE DATABASE mautictest; CREATE USER travis@"%"; GRANT ALL on mautictest.* to travis@"%"; GRANT SUPER ON *.* TO travis@"%";'
              echo "<?php
              \\\$parameters = array(
                  'db_driver' => 'pdo_mysql',
                  'db_host' => '127.0.0.1',
                  'db_port' => 3306,
                  'db_name' => 'mautictest',
                  'db_user' => 'travis',
                  'db_password' => '',
                  'db_table_prefix' => '',
                  'hosted_plan' => 'pro'
              );" > app/config/local.php
              export SYMFONY_ENV="test"
              bin/phpunit -d memory_limit=2048M --bootstrap vendor/autoload.php --configuration plugins/IntegrationsBundle/phpunit.xml --fail-on-warning  --testsuite=all
            """
          }
        }
      }
    }
    stage('Static Analysis') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            dir('plugins/IntegrationsBundle') {
              sh """
                composer run-script phpstan
              """
            }
          }
        }
      }
    }
    stage('Styling') {
      steps {
        container('hosted-tester') {
          ansiColor('xterm') {
            dir('plugins/IntegrationsBundle') {
              sh """
                vendor/bin/ecs check .
              """
            }
          }
        }
      }
    }
    stage('Automerge to beta') {
      when {
        changeRequest target: 'staging'
      }
      steps {
        script {
          def githubPR = httpRequest acceptType: 'APPLICATION_JSON', authentication: 'c6c13656-2d08-4391-b324-95085e23ce59', url: "https://api.github.com/repos/mautic-inc/plugin-integrations/pulls/${CHANGE_ID}", validResponseCodes: '200'
          def githubPRObject = readJSON text: githubPR.getContent()

          echo "Title: "+githubPRObject.title
          if(githubPRObject.title ==~ /(?i).*(^|[^a-z])wip($|[^a-z]).*/) {
            echo "PR still WIP. Failing the build to prevent accidental merge"
            error("PR still WIP. Failing the build to prevent accidental merge")
          }
          else {
            echo "Merging PR to beta"
            withEnv(["PRNUMBER=${CHANGE_ID}"]) {
            sshagent (credentials: ['1a066462-6d24-4247-bef6-1da084c8f484']) {
            dir('plugins/IntegrationsBundle') {
              sh '''
                git config --global user.email "9725490+mautibot@users.noreply.github.com"
                git config --global user.name "Jenkins"
                gitsha="$(git rev-parse HEAD)"
                if [ "$(git --no-pager show -s HEAD --format='%ae')" = "nobody@nowhere" ]; then
                    echo "Skipping Jenkinse's merge commit which we do not need"
                    gitsha="$(git rev-parse HEAD~1)"
                fi
                git remote set-branches --add origin beta
                git fetch -q
                git checkout origin/beta
                git merge -m "Merge commit '$gitsha' from PR $PRNUMBER into beta" "$gitsha"
                git push origin HEAD:beta
                git checkout "$gitsha"
              '''
            }}}
          }
        }
      }
    }
    stage('Set Revision') {
      when {
        not {
          changeRequest()
        }
        anyOf {
          branch 'beta'
          branch 'staging';
        }
      }
      steps {
        script {
          echo "Updating IntegrationsBundle submodule in mautic-cloud repo (branch ${BRANCH_NAME})"
          sshagent (credentials: ['1a066462-6d24-4247-bef6-1da084c8f484']) {
            sh '''
              git config --global user.email "9725490+mautibot@users.noreply.github.com"
              git config --global user.name "Jenkins"
              git clone git@github.com:mautic-inc/mautic-cloud.git -b $BRANCH_NAME
              cd mautic-cloud
              if [ -n "$(grep IntegrationsBundle .gitmodules)" ]; then
                git submodule update --init --recursive plugins/IntegrationsBundle/
                cd plugins/IntegrationsBundle/
                git reset --hard origin/$BRANCH_NAME
                SUBMODULE_COMMIT=$(git log -1 | awk 'NR==1{print $2}')
                cd ../..
                git add plugins/IntegrationsBundle
                git commit -m "IntegrationsBundle updated with commit $SUBMODULE_COMMIT"
                git push
              fi
            '''
          }
        }
      }
    }
  }
  post {
    failure {
      script {
        if (BRANCH_NAME ==~ /^(beta|staging)$/) {
          slackSend (color: '#FF0000', message: "${REPO_NAME} failed build on branch ${env.BRANCH_NAME}. (${env.BUILD_URL}console)")
        }
        if (env.CHANGE_AUTHOR != null && !env.CHANGE_TITLE.contains("WIP")) {
          def githubToSlackMap = [
            'alanhartless':'alan.hartless',
            'anton-vlasenko':'anton.vlasenko',
            'dongilbert':'don.gilbert',
            'escopecz':'jan.linhart',
            'galvani':'jan.kozak',
            'Gregy':'petr.gregor',
            'hluchas':'lukas.drahy',
            'javjim-mautic':'javier.jimeno',
            'mtshaw3':'mike.shaw',
            'ondrejsibl':'ondrej.sibl',
            'pavel-hladik':'pavel.hladik'
          ]
          if (githubToSlackMap.("${env.CHANGE_AUTHOR}")) {
            slackSend (channel: "@"+"${githubToSlackMap.("${env.CHANGE_AUTHOR}")}", color: '#FF0000', message: "${REPO_NAME} failed build on ${env.BRANCH_NAME} (${env.CHANGE_TITLE})\nchange: ${env.CHANGE_URL}\nbuild: ${env.BUILD_URL}console")
          }
          else {
            slackSend (color: '#FF0000', message: "${REPO_NAME} failed build on ${env.BRANCH_NAME} (${env.CHANGE_TITLE})\nchange: ${env.CHANGE_URL}\nbuild: ${env.BUILD_URL}console\nsending alert to channel, there is no Github to Slack mapping for '${CHANGE_AUTHOR}'")
          }
        }
      }
    }
    fixed {
      script {
        if (BRANCH_NAME ==~ /^(beta|staging)$/) {
          slackSend (color: '#00FF00', message: "${REPO_NAME} build on branch ${env.BRANCH_NAME} is fixed. (${env.BUILD_URL}console)")
        }
      }
    }
  }
}
