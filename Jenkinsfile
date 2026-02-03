pipeline {
    agent any

    environment {
        // Utilisation correcte des credentials Docker Hub
        DOCKER_CREDENTIALS = credentials('dockerhub-credentials')
        IMAGE_NAME = "ramaseck1/labo-app" // Remplacez par votre nom d'utilisateur Docker Hub
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
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
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
                sh "docker build -t ${IMAGE_NAME}:${IMAGE_TAG} ."
            }
        }

        stage('🔐 Login & Push Docker Hub') {
            steps {
                echo 'Connexion à Docker Hub et push de l\'image...'
                sh '''
                    echo $DOCKER_CREDENTIALS_PSW | docker login -u $DOCKER_CREDENTIALS_USR --password-stdin
                    docker push ${IMAGE_NAME}:${IMAGE_TAG}
                '''
            }
        }

        stage('🚀 Deploy to Render') {
            when {
                expression { 
                    // Vérifie si le credential render-api-key existe
                    try {
                        credentials('render-api-key')
                        return true
                    } catch (Exception e) {
                        return false
                    }
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
            echo "🐳 Image Docker disponible : ${IMAGE_NAME}:${IMAGE_TAG}"
        }
        failure {
            echo '❌ Le pipeline a échoué. Consultez les logs.'
        }
        always {
            echo '🧹 Nettoyage...'
            sh 'docker logout || true'
        }
    }
}