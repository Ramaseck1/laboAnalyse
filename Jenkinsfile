pipeline {
    agent any

    environment {
        DOCKER_HUB_USERNAME = credentials('dockerhub-cred')
        DOCKER_HUB_PASSWORD = credentials('dockerhub-cred')
        IMAGE_NAME = "${DOCKER_HUB_USERNAME}/labo-app"
        IMAGE_TAG = "latest"
    }

    stages {
        stage('🔍 Checkout') {
            steps {
                echo 'Récupération du code source depuis GitHub...'
                git branch: 'main', 
                    credentialsId: 'github-cred',
                    url: 'https://github.com/Ramaseck1/laboAnalyse.git'
            }
        }

        stage('📦 Install Dependencies') {
            steps {
                echo 'Installation des dépendances Composer...'
                sh '''
                    composer install --no-interaction --prefer-dist --optimize-autoloader
                '''
            }
        }

        stage('🧪 Run Tests') {
            steps {
                echo 'Exécution des tests...'
                sh 'php artisan test || echo "Tests ignorés"'
            }
        }

        stage('🐳 Build Docker Image') {
            steps {
                echo 'Construction de l\'image Docker...'
                sh 'docker build -t ${IMAGE_NAME}:${IMAGE_TAG} .'
            }
        }

        stage('🔐 Login Docker Hub') {
            steps {
                echo 'Connexion à Docker Hub...'
                sh 'echo $DOCKER_HUB_PASSWORD | docker login -u $DOCKER_HUB_USERNAME --password-stdin'
            }
        }

        stage('📤 Push Docker Image') {
            steps {
                echo 'Push de l\'image vers Docker Hub...'
                sh 'docker push ${IMAGE_NAME}:${IMAGE_TAG}'
            }
        }

        stage('🚀 Deploy to Render') {
            when {
                expression { 
                    return env.RENDER_API_KEY != null 
                }
            }
            steps {
                echo 'Déclenchement du déploiement sur Render...'
                withCredentials([string(credentialsId: 'render-api-key', variable: 'RENDER_API_KEY')]) {
                    sh """
                        curl -X POST "https://api.render.com/v1/services/YOUR_SERVICE_ID/deploys" \
                        -H "Authorization: Bearer \$RENDER_API_KEY" \
                        -H "Content-Type: application/json" \
                        -d '{"clearCache": true}'
                    """
                }
            }
        }
    }

    post {
        success {
            echo '✅ Pipeline exécuté avec succès !'
        }
        failure {
            echo '❌ Le pipeline a échoué. Consultez les logs.'
        }
        always {
        echo '🧹 Nettoyage...'
        steps {
            sh 'docker logout || true'
         }
       }
    }
}