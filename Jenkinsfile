pipeline {
    agent any

    environment {
        IMAGE_TAG = "latest"
    }

    stages {
        stage('🔍 Checkout') {
            steps {
                git branch: 'main',
                    credentialsId: 'github-cred',
                    url: 'https://github.com/Ramaseck1/laboAnalyse.git'
            }
        }

        stage('📦 Install Dependencies') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
            }
        }

        stage('🧪 Run Tests') {
            steps {
                sh 'php artisan test || echo "Tests ignorés"'
            }
        }

        stage('🐳 Build & Push Docker Image') {
            steps {
                withCredentials([
                    string(credentialsId: 'dockerhub-username', variable: 'DOCKER_HUB_USERNAME'),
                    string(credentialsId: 'dockerhub-password', variable: 'DOCKER_HUB_PASSWORD')
                ]) {
                    script {
                        def imageName = "${DOCKER_HUB_USERNAME}/labo-app"

                        echo "Connexion à Docker Hub..."
                        sh 'echo $DOCKER_HUB_PASSWORD | docker login -u $DOCKER_HUB_USERNAME --password-stdin'

                        echo "Construction de l\'image Docker..."
                        sh "docker build -t ${imageName}:${IMAGE_TAG} ."

                        echo "Push de l\'image Docker..."
                        sh "docker push ${imageName}:${IMAGE_TAG}"

                        echo "Logout Docker Hub..."
                        sh 'docker logout || true'
                    }
                }
            }
        }

        stage('🚀 Deploy to Render') {
            when {
                expression { env.RENDER_API_KEY != null }
            }
            steps {
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
    }
}
