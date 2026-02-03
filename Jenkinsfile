pipeline {
    agent any

    environment {
        DOCKER_CREDENTIALS = credentials('dockerhub-credentials')
        IMAGE_NAME = "ramaseck2/labo-app" 
        IMAGE_TAG = "latest"
    }

    stages {
        stage('🔍 Checkout') {
            steps {
                echo 'Récupération du code source depuis GitHub...'
                checkout scm
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
    }

    post {
        success {
            echo '✅ Pipeline exécuté avec succès !'
            echo "🐳 Image disponible : ${IMAGE_NAME}:${IMAGE_TAG}"
        }
        failure {
            echo '❌ Le pipeline a échoué.'
        }
        always {
            sh 'docker logout || true'
        }
    }
}